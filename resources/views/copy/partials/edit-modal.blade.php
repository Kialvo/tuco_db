<!-- EDIT COPY MODAL -->
<div id="editModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white border border-gray-200 p-6 rounded-2xl shadow-2xl max-w-md w-full mx-2 relative">

        <!-- X -->
        <button id="editModalCloseBtn" type="button"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 focus:outline-none">
            <x-icon name="x" size="sm" class="inline" />
        </button>

        <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Copy</h2>

        <div id="editModalErrors"
             class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 hidden"></div>

        <form id="editCopyForm" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Copy Value <span class="text-red-500">*</span>
                </label>
                <input type="text" id="edit_copy_val" name="copy_val" required
                       class="block w-full border border-gray-300 rounded-md text-sm px-3 py-2
                              focus:ring-green-500 focus:border-green-500">
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="bg-green-600 text-white px-5 py-2 rounded shadow-sm hover:bg-green-700
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 text-sm">
                    Update Copy
                </button>
            </div>
        </form>
    </div>
</div>
