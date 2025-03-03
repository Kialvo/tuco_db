<!-- Contact Details Modal (initially hidden) -->
<div
    class="fixed inset-0 z-50 hidden"
    id="contactModal"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- A centered container -->
    <div
        class="max-w-lg mx-auto mt-20 bg-white p-6 rounded shadow-lg relative"
    >
        <!-- Close button (top-right "X") -->
        <button
            type="button"
            class="absolute top-3 right-3 text-2xl text-gray-600 hover:text-gray-800"
            id="closeContactModal"
        >
            &times;
        </button>

        <!-- Modal heading -->
        <h2
            class="text-xl font-semibold mb-4 text-gray-800"
            id="modalContactTitle"
        >
            Contact Details
        </h2>

        <!-- Vertical stack for contact fields -->
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
        </div>

        <!-- Bottom Close button (optional) -->
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
