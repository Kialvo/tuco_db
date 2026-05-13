{{-- Client Details modal (storages) --}}
<div id="clientModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 flex"
     aria-labelledby="modalClientTitle"
     role="dialog"
     aria-modal="true">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col max-h-[90vh]">
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 flex-shrink-0">
            <h2 id="modalClientTitle" class="text-base font-bold text-gray-800">Client Details</h2>
            <button type="button" id="closeClientModal"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                <x-icon name="x" size="lg" />
            </button>
        </div>

        {{-- Body --}}
        <div class="px-5 py-4 overflow-y-auto slim-scroll flex-1">
            <dl class="divide-y divide-gray-100 text-sm">
                <div class="py-2 grid grid-cols-3 gap-2">
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</dt>
                    <dd id="modalClientName" class="col-span-2 text-gray-800"></dd>
                </div>
                <div class="py-2 grid grid-cols-3 gap-2">
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</dt>
                    <dd id="modalClientEmail" class="col-span-2 text-gray-800 break-all"></dd>
                </div>
                <div class="py-2 grid grid-cols-3 gap-2">
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Company</dt>
                    <dd id="modalClientCompany" class="col-span-2 text-gray-800"></dd>
                </div>
            </dl>

            <div class="mt-4 p-4 bg-gray-50 border border-gray-100 rounded-lg">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Storages for this client</p>
                <div id="modalClientStorages" class="text-sm text-gray-700 space-y-1"></div>
            </div>

            <div class="mt-3 p-4 bg-gray-50 border border-gray-100 rounded-lg">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Websites for this client</p>
                <div id="modalClientWebsites" class="text-sm text-gray-700 space-y-1"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end rounded-b-2xl flex-shrink-0">
            <button type="button" id="closeClientModalBottom"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Close
            </button>
        </div>
    </div>
</div>
