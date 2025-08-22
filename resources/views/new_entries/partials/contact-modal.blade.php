<div id="contactModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
    <div class="bg-white rounded shadow-lg w-[520px] p-4">
        <div class="flex justify-between items-center mb-2">
            <h3 class="text-sm font-semibold text-gray-700">Contact</h3>
            <button id="closeContactModal" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <div class="text-xs space-y-1">
            <div><span class="font-medium">Name:</span> <span id="modalContactName"></span></div>
            <div><span class="font-medium">Email:</span> <span id="modalContactEmail"></span></div>
            <div><span class="font-medium">Phone:</span> <span id="modalContactPhone"></span></div>
            <div><span class="font-medium">Facebook:</span> <span id="modalContactFacebook"></span></div>
            <div><span class="font-medium">Instagram:</span> <span id="modalContactInstagram"></span></div>
            <div class="mt-2">
                <div class="font-medium">Websites:</div>
                <div id="modalContactWebsites" class="pl-3"></div>
            </div>
        </div>

        <div class="mt-3 flex justify-end">
            <button id="closeContactModalBottom" class="bg-gray-500 text-white px-3 py-1 rounded text-xs">Close</button>
        </div>
    </div>
</div>
