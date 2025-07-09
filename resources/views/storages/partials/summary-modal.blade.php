<div id="summaryModal"
     class="fixed inset-0 bg-gray-900 bg-opacity-50 z-[999999] hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-72 text-xs space-y-3">
        <h3 class="text-base font-semibold text-gray-700">Summary Settings</h3>
        <div id="summaryOptions" class="space-y-2">
            {{-- Will be filled dynamically --}}
        </div>
        <div class="flex justify-end gap-2 pt-3">
            <button id="summaryCancelBtn" class="text-gray-600 hover:text-black">Cancel</button>
            <button id="summaryApplyBtn" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                Apply
            </button>
        </div>
    </div>
</div>
