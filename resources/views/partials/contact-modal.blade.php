<!-- Contact Details Modal (hidden by default) -->
<div
    class="fixed z-10 inset-0 overflow-y-auto hidden"
    id="contactModal"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <div
        class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
    >
        <!-- Overlay background -->
        <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            aria-hidden="true"
            id="contactModalOverlay"
        ></div>

        <!-- Modal panel -->
        <div
            class="inline-block align-bottom bg-white rounded-lg text-left
                   overflow-hidden shadow-xl transform transition-all
                   sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
        >
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modalContactTitle">
                        Contact Details
                    </h3>

                    <p>
                        <strong>Name:</strong>
                        <span id="modalContactName"></span>
                    </p>
                    <p>
                        <strong>Email:</strong>
                        <span id="modalContactEmail"></span>
                    </p>
                    <p>
                        <strong>Phone:</strong>
                        <span id="modalContactPhone"></span>
                    </p>
                    <p>
                        <strong>Facebook:</strong>
                        <span id="modalContactFacebook"></span>
                    </p>
                    <p>
                        <strong>Instagram:</strong>
                        <span id="modalContactInstagram"></span>
                    </p>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                    type="button"
                    class="w-full inline-flex justify-center rounded-md border
                           border-gray-300 shadow-sm px-4 py-2 bg-white text-base
                           font-medium text-gray-700 hover:bg-gray-50 focus:outline-none
                           focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                           sm:ml-3 sm:w-auto sm:text-sm"
                    id="closeContactModal"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
