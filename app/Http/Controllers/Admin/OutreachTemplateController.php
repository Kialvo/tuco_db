<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OutreachTemplateController extends Controller
{
    public function index()
    {
        $languages = config('outreach.languages');
        $templates = config('outreach.templates');

        return view('admin.outreach_templates.index', compact('languages', 'templates'));
    }

    public function update(Request $request, string $language, string $type)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        $config = config('outreach');

        $validLanguages = array_keys($config['languages'] ?? []);
        $validTypes     = ['first', 'followup'];

        if (!in_array($language, $validLanguages, true) || !in_array($type, $validTypes, true)) {
            return response()->json(['error' => 'Invalid language or template type.'], 422);
        }

        $config['templates'][$language][$type]['subject'] = $request->input('subject');
        $config['templates'][$language][$type]['body']    = $request->input('body');

        $php = "<?php\n\nreturn " . var_export($config, true) . ";\n";

        file_put_contents(config_path('outreach.php'), $php);

        // Clear config cache so next request picks up the new values
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(config_path('outreach.php'), true);
        }

        return response()->json(['success' => true]);
    }
}
