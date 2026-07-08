<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Storage;
use App\Models\Website;
use App\Services\StorageCalculator;
use App\Support\PublicationStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Phase 3 — a "publication" in the Campaigns module IS a `storage` row
 * (linked via storage.lb_campaign_id). This controller exposes the
 * campaign-facing subset of storage fields; everything else is edited in
 * the Storage module ("Details" → storages.edit).
 */
class PublicationController extends Controller
{
    /** Campaign-facing columns editable inline from the campaign page. */
    private const INLINE_FIELDS = [
        'price'                     => 'nullable|numeric|min:0',   // virtual → menford (see StorageCalculator::setPrice)
        'article_url'               => 'nullable|url|max:500',
        'publication_date'          => 'nullable|date',            // "Live Date"
        'copywriter_commision_date' => 'nullable|date',            // "Sent to Copy"
        'copywriter_submission_date'=> 'nullable|date',            // "Copy Received"
        'article_sent_to_publisher' => 'nullable|date',            // "Sent to Blog"
    ];

    /*======================================================================
    |  STORE — create a NEW storage row from the campaign modal
    ======================================================================*/
    public function store(Request $request, Campaign $campaign)
    {
        $data = $this->validated($request);

        $attrs = $this->mappedAttributes($data);
        $attrs['lb_campaign_id'] = $campaign->id;
        $attrs['campaign_code']  = $campaign->code;

        StorageCalculator::setPrice($attrs, (float) $data['price']);

        $storage = Storage::create($attrs);

        return response()->json(['status' => 'success', 'id' => $storage->id]);
    }

    /*======================================================================
    |  UPDATE — edit the campaign-facing subset of a storage row
    ======================================================================*/
    public function update(Request $request, Storage $storage)
    {
        $data  = $this->validated($request);
        $attrs = $this->mappedAttributes($data);

        // Recompute derived totals against the FULL row, not just the subset.
        $payload = array_merge($storage->getAttributes(), $attrs);
        StorageCalculator::setPrice($payload, (float) $data['price']);

        $storage->fill([
            ...$attrs,
            'menford'           => $payload['menford'],
            'total_cost'        => $payload['total_cost'],
            'total_revenues'    => $payload['total_revenues'],
            'profit'            => $payload['profit'],
            'copywriter_period' => $payload['copywriter_period'] ?? $storage->copywriter_period,
            'publisher_period'  => $payload['publisher_period'] ?? $storage->publisher_period,
        ])->save();

        return response()->json(['status' => 'success', 'id' => $storage->id]);
    }

    /*======================================================================
    |  INLINE STATUS UPDATE (unified slug list)
    ======================================================================*/
    public function updateStatus(Request $request, Storage $storage)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(PublicationStatus::slugs())],
        ]);

        $storage->update($data);

        return response()->json([
            'status'       => 'success',
            'value'        => $storage->status,
            'status_group' => $storage->status_group,
        ]);
    }

    /*======================================================================
    |  INLINE FIELD UPDATE (editable cells in the publications table)
    ======================================================================*/
    public function inlineUpdate(Request $request, Storage $storage)
    {
        $field = (string) $request->input('field');
        abort_unless(array_key_exists($field, self::INLINE_FIELDS), 422, 'Field not editable');

        $validated = $request->validate(['value' => self::INLINE_FIELDS[$field]]);
        $value = $validated['value'] ?? null;

        if ($field === 'price') {
            $payload = $storage->getAttributes();
            StorageCalculator::setPrice($payload, (float) ($value ?? 0));
            $storage->update([
                'menford'        => $payload['menford'],
                'total_revenues' => $payload['total_revenues'],
                'profit'         => $payload['profit'],
            ]);
            $value = $storage->total_revenues;
        } else {
            $storage->update([$field => $value]);
        }

        return response()->json(['status' => 'success', 'field' => $field, 'value' => $value]);
    }

    /*======================================================================
    |  EDIT AJAX – prefill the campaign-page modal
    ======================================================================*/
    public function editAjax(Storage $storage)
    {
        $storage->load('site:id,domain_name');
        $fdate = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d') : null;

        return response()->json(['status' => 'success', 'data' => [
            'id'                          => $storage->id,
            'website_id'                  => $storage->website_id,
            'site'                        => $storage->publisher_domain,
            'status'                      => $storage->status,
            'price'                       => (float) $storage->total_revenues,
            'article_url'                 => $storage->article_url,
            'publication_date'            => $fdate($storage->publication_date),
            'copywriter_commision_date'   => $fdate($storage->copywriter_commision_date),
            'copywriter_submission_date'  => $fdate($storage->copywriter_submission_date),
            'article_sent_to_publisher'   => $fdate($storage->article_sent_to_publisher),
        ]]);
    }

    /*======================================================================
    |  DESTROY = UNLINK from the campaign (storage row is accounting data
    |  and always survives; deleting rows happens in the Storage module)
    ======================================================================*/
    public function destroy(Storage $storage)
    {
        $storage->update(['lb_campaign_id' => null, 'campaign_code' => null]);

        return response()->json(['status' => 'success']);
    }

    /*======================================================================
    |  SEARCH storage rows to link (Select2 AJAX on the campaign page)
    ======================================================================*/
    public function searchStorages(Request $request, Campaign $campaign)
    {
        $q = trim((string) $request->get('q', ''));

        $query = Storage::with(['site:id,domain_name', 'lbCampaign:id,code'])
            ->orderByDesc('id');

        if (! $request->boolean('include_assigned')) {
            $query->whereNull('lb_campaign_id');
        } else {
            // still hide rows already in THIS campaign
            $query->where(fn ($w) => $w->whereNull('lb_campaign_id')->orWhere('lb_campaign_id', '!=', $campaign->id));
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->whereHas('site', fn ($s) => $s->where('domain_name', 'like', "%{$q}%"))
                    ->orWhere('website', 'like', "%{$q}%")
                    ->orWhere('domain_name', 'like', "%{$q}%")
                    ->orWhere('article_url', 'like', "%{$q}%");
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        $rows = $query->limit(20)->get();

        return response()->json([
            'results' => $rows->map(fn (Storage $s) => [
                'id'   => $s->id,
                'text' => '#' . $s->id . ' — ' . ($s->publisher_domain ?: 'no domain'),
                'domain'   => $s->publisher_domain,
                'status'   => $s->status_label ?? '—',
                'price'    => (float) $s->total_revenues,
                'pub_date' => $s->publication_date ? \Illuminate\Support\Carbon::parse($s->publication_date)->format('d/m/Y') : null,
                'campaign' => $s->lbCampaign?->code,
            ]),
        ]);
    }

    /*======================================================================
    |  LINK existing storage rows to the campaign
    ======================================================================*/
    public function linkExisting(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:storage,id',
        ]);

        $linked = 0;
        foreach (Storage::whereIn('id', $data['ids'])->get() as $storage) {
            $storage->update([
                'lb_campaign_id' => $campaign->id,
                'campaign_code'  => $campaign->code,
            ]);
            $linked++;
        }

        return response()->json(['status' => 'success', 'linked' => $linked]);
    }

    /*======================================================================
    |  WEBSITES search (Publisher dropdown in the New Publication modal)
    ======================================================================*/
    public function websitesSearch(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $sites = Website::query()
            ->when($q !== '', fn ($w) => $w->where('domain_name', 'like', "%{$q}%"))
            ->orderBy('domain_name')
            ->limit(20)
            ->get(['id', 'domain_name']);

        return response()->json([
            'results' => $sites->map(fn ($s) => ['id' => $s->id, 'text' => $s->domain_name]),
        ]);
    }

    /*======================================================================
    |  Helpers
    ======================================================================*/
    private function validated(Request $request): array
    {
        return $request->validate([
            'website_id'                  => 'nullable|integer|exists:websites,id',
            'site'                        => 'required_without:website_id|nullable|string|max:255',
            'status'                      => ['required', Rule::in(PublicationStatus::slugs())],
            'price'                       => 'required|numeric|min:0',
            'article_url'                 => 'nullable|url|max:500',
            'publication_date'            => 'nullable|date',
            'copywriter_commision_date'   => 'nullable|date',
            'copywriter_submission_date'  => 'nullable|date',
            'article_sent_to_publisher'   => 'nullable|date',
        ]);
    }

    /** Map validated modal input onto storage columns (price handled separately). */
    private function mappedAttributes(array $data): array
    {
        return [
            'website_id'                  => $data['website_id'] ?? null,
            // free-typed publisher (no websites match) → legacy text column
            'website'                     => ($data['website_id'] ?? null) ? null : ($data['site'] ?? null),
            'status'                      => $data['status'],
            'article_url'                 => $data['article_url'] ?? null,
            'publication_date'            => $data['publication_date'] ?? null,
            'copywriter_commision_date'   => $data['copywriter_commision_date'] ?? null,
            'copywriter_submission_date'  => $data['copywriter_submission_date'] ?? null,
            'article_sent_to_publisher'   => $data['article_sent_to_publisher'] ?? null,
        ];
    }
}
