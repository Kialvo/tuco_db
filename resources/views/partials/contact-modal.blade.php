<!-- Contact Details Modal (initially hidden) -->
<div
    class="fixed inset-0 z-50 flex items-center justify-center hidden"
    id="contactModal"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- The semi-transparent, blurred background -->
    <div
        class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm"
        id="contactModalOverlay"
        aria-hidden="true"
    ></div>

    <!-- The white panel (the actual modal content) -->
    <div
        class="relative bg-white w-full max-w-md mx-4 sm:mx-0 rounded shadow-lg p-6"
    >
        <!-- Close button (X) in the top-right corner -->
        <button
            type="button"
            class="absolute top-3 right-3 text-gray-500 hover:text-gray-700"
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

        <!-- Contact Fields -->
        <div class="space-y-2">
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

        <!-- Footer / Close button -->
        <div class="mt-6 text-right">
            <button
                type="button"
                class="inline-block px-4 py-2 bg-blue-600 text-white font-medium
                       rounded hover:bg-blue-700 focus:outline-none"
                id="closeContactModalBottom"
            >
                Close
            </button>
        </div>
    </div>
</div>
