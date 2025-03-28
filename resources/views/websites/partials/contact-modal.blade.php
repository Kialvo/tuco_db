<!-- Contact Details Modal (initially hidden) -->
<div
    class="fixed inset-0 z-50 flex items-center justify-center hidden"
    id="contactModal"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Modal panel (narrow, vertical) -->
    <div
        class="bg-white rounded shadow-lg relative w-auto max-w-sm p-6"
    >
        <!-- Close button (X) in top-right corner -->
        <button
            type="button"
            class="absolute top-3 right-3 text-2xl text-gray-600 hover:text-gray-800"
            id="closeContactModal"
        >
            &times;
        </button>

        <!-- Heading -->
        <h2
            class="text-xl font-semibold mb-4 text-gray-800"
            id="modalContactTitle"
        >
            Contact Details
        </h2>

        <!-- Fields in a vertical stack -->
        <div class="flex flex-col space-y-2">
            <p>
                <strong>Name:</strong>
                <span id="modalContactName" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Email:</strong>
                <span id="modalContactEmail" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Phone:</strong>
                <span id="modalContactPhone" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Facebook:</strong>
                <span id="modalContactFacebook" class="ml-1 text-gray-700"></span>
            </p>
            <p>
                <strong>Instagram:</strong>
                <span id="modalContactInstagram" class="ml-1 text-gray-700"></span>
            </p>

            <div class="mt-4">
                <strong>Websites for this contact:</strong>
                <!-- We'll populate this dynamically via JavaScript -->
                <div id="modalContactWebsites" class="text-sm mt-2"></div>
            </div>
        </div>

        <!-- Optional bottom Close button -->
        <div class="mt-6 text-right">
            <button
                type="button"
                class="inline-block px-4 py-2 bg-blue-600 text-white
                       font-medium rounded hover:bg-blue-700 focus:outline-none"
                id="closeContactModalBottom"
            >
                Close
            </button>
        </div>
    </div>
</div>
