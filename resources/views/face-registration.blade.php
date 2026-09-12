<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SURGE - Face Registration</title>
    <script src="{{ asset('js/face-api.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/face-recognition.css') }}">
    <link rel="stylesheet" href="{{ asset('css/face-registration.css') }}">
</head>

<body>
    <!-- Centered Square Toast Notification -->
    <div id="toast-container" class="toast-info">
        <i id="toast-icon" data-lucide="info"></i>
        <span id="toast-message">Message text</span>
    </div>

    <div class="registration-card">
        <header style="margin-block-end: 1.5rem; text-align: center;">
            <h2 style="color: #feffff;">Employee Face Registration</h2>
            <p style="color: #feffff;">Assign facial ID to unmapped employees</p>
        </header>

        <!-- Employee Selection Dropdown -->
        <div class="form-group">
            <label for="employee_id">Select Unregistered Employee</label>
            <select id="employee_id" class="form-control" onchange="validateEmployeeSelection()" required>
                <option value="">-- Choose Employee --</option>
                @foreach ($employees as $employee)
                <option value="{{ $employee->employeeid }}">
                    {{ $employee->lastname }}, {{ $employee->firstname }} (ID: {{ $employee->employeeid }})
                </option>
                @endforeach
            </select>
            <small id="employee-validation-msg" style="color: #ef4444; display: none; margin-block-start: 5px;"></small>
        </div>

        <!-- Camera / Preview Container -->
        <div class="viewport-wrapper viewport-container">
            <video id="webcam" autoplay muted playsinline></video>
            <canvas id="overlay-canvas"></canvas>
            <img id="captured-preview" alt="Captured Frame Preview">

            <!-- Realtime Distance/Position Guidance Overlay with Icon -->
            <div id="guidance-overlay" class="status-warn">
                <i id="guidance-icon" data-lucide="loader"></i>
                <span id="guidance-text">Initializing Camera...</span>
            </div>
        </div>

        <!-- Action Controls -->
        <div class="btn-container">
            <button id="btn-capture" class="btn-reg btn-capture" onclick="captureFace()">
                <i data-lucide="camera"></i> Capture Face
            </button>
            <button id="btn-remove" class="btn-reg btn-remove" onclick="resetCapture()" style="display: none;">
                <i data-lucide="trash-2"></i> Remove / Retake
            </button>
            <button id="btn-save" class="btn-reg btn-save" onclick="saveRegistration()" style="display: none;">
                <i data-lucide="save"></i> Save Registration
            </button>
        </div>
    </div>

    <script>
        let currentDescriptor = null;
        let isEmployeeRegistered = false;
        let faceDetectionInterval = null;
        let isFaceIdeal = false;

        const MODEL_URL = "{{ asset('models') }}";
        const video = document.getElementById('webcam');
        const overlayCanvas = document.getElementById('overlay-canvas');
        const guidanceOverlay = document.getElementById('guidance-overlay');
        const previewImg = document.getElementById('captured-preview');

        const btnCapture = document.getElementById('btn-capture');
        const btnRemove = document.getElementById('btn-remove');
        const btnSave = document.getElementById('btn-save');

        // Initial Load Setup
        window.addEventListener('load', async () => {
            lucide.createIcons();

            if (typeof faceapi === 'undefined') {
                showToast("Error: face-api.js failed to load.", "error");
                return;
            }

            try {
                await Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                startVideo();
            } catch (err) {
                console.error("Model loading error:", err);
                showToast("Error loading face recognition models.", "error");
            }
        });

        // Toast Handler with Icons
        function showToast(message, type = "info") {
            const toast = document.getElementById('toast-container');
            const toastMsg = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');

            toastMsg.innerText = message;
            toast.className = `toast-${type}`;

            // Map Lucide icon depending on status
            const iconMap = {
                'success': 'check-circle-2',
                'error': 'alert-triangle',
                'info': 'info'
            };

            toastIcon.setAttribute('data-lucide', iconMap[type] || 'info');
            lucide.createIcons();

            // Trigger Animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            // Auto Fade Out
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3500);
        }

        // Start Webcam Feed
        function startVideo() {
            navigator.mediaDevices.getUserMedia({
                    video: true
                })
                .then(stream => {
                    video.srcObject = stream;
                    video.addEventListener('play', onVideoPlay);
                })
                .catch(err => {
                    console.error("Webcam error:", err);
                    showToast("Unable to access camera.", "error");
                    setGuidance("Camera Error", "danger", "camera-off");
                });
        }

        // Continuous Detection Loop
        function onVideoPlay() {
            const displaySize = {
                width: video.clientWidth || 640,
                height: video.clientHeight || 480
            };
            faceapi.matchDimensions(overlayCanvas, displaySize);

            if (faceDetectionInterval) clearInterval(faceDetectionInterval);

            faceDetectionInterval = setInterval(async () => {
                if (video.paused || video.ended || previewImg.style.display === 'block') return;

                const detection = await faceapi.detectSingleFace(video, new faceapi.SsdMobilenetv1Options({
                    minConfidence: 0.5
                }));

                const ctx = overlayCanvas.getContext('2d');
                ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);

                if (!detection) {
                    isFaceIdeal = false;
                    setGuidance("No Face Detected", "danger", "user-x");
                    return;
                }

                const resizedDetection = faceapi.resizeResults(detection, displaySize);
                const box = resizedDetection.box;

                // Draw bounding box
                ctx.strokeStyle = isFaceIdeal ? '#10b981' : '#f59e0b';
                ctx.lineWidth = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);

                // Analyze Distance & Position
                evaluateFacePosition(box, displaySize);
            }, 200);
        }

        // Position & Distance Validation Rules
        function evaluateFacePosition(box, displaySize) {
            const videoWidth = displaySize.width;
            const videoHeight = displaySize.height;

            const faceCenterX = box.x + (box.width / 2);
            const faceCenterY = box.y + (box.height / 2);

            const frameCenterX = videoWidth / 2;
            const frameCenterY = videoHeight / 2;

            const faceWidthRatio = box.width / videoWidth;

            // 1. Distance checks
            if (faceWidthRatio < 0.25) {
                isFaceIdeal = false;
                setGuidance("Move Closer to Camera", "warn", "zoom-in");
                return;
            }
            if (faceWidthRatio > 0.60) {
                isFaceIdeal = false;
                setGuidance("Move Farther Back", "warn", "zoom-out");
                return;
            }

            // 2. Alignment checks
            const offsetX = Math.abs(faceCenterX - frameCenterX) / videoWidth;
            const offsetY = Math.abs(faceCenterY - frameCenterY) / videoHeight;

            if (offsetX > 0.18) {
                isFaceIdeal = false;
                setGuidance(
                    faceCenterX < frameCenterX ? "Move Slightly Right" : "Move Slightly Left",
                    "warn",
                    faceCenterX < frameCenterX ? "arrow-right" : "arrow-left"
                );
                return;
            }

            if (offsetY > 0.18) {
                isFaceIdeal = false;
                setGuidance(
                    faceCenterY < frameCenterY ? "Move Down" : "Move Up",
                    "warn",
                    faceCenterY < frameCenterY ? "arrow-down" : "arrow-up"
                );
                return;
            }

            // 3. Perfect condition
            isFaceIdeal = true;
            setGuidance("Perfect! Hold still...", "ok", "check-circle");
        }

        // Update Guidance Text & Icon Dynamic Renderer
        function setGuidance(text, status, iconName) {
            const guidanceText = document.getElementById('guidance-text');
            const guidanceIcon = document.getElementById('guidance-icon');

            guidanceText.innerText = text;
            guidanceOverlay.className = `status-${status}`;

            if (iconName && guidanceIcon.getAttribute('data-lucide') !== iconName) {
                guidanceIcon.setAttribute('data-lucide', iconName);
                lucide.createIcons();
            }
        }

        // Employee Selection Validation
        async function validateEmployeeSelection() {
            const employeeSelect = document.getElementById('employee_id');
            const employeeId = employeeSelect.value;
            const msg = document.getElementById('employee-validation-msg');

            if (msg) msg.style.display = 'none';
            btnCapture.disabled = false;
            isEmployeeRegistered = false;

            if (!employeeId) return;

            try {
                const response = await fetch(`/check-face-registered/${employeeId}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.is_registered) {
                        isEmployeeRegistered = true;
                        btnCapture.disabled = true;

                        if (msg) {
                            msg.innerText = "Warning: Face ID is already registered for this employee.";
                            msg.style.display = 'block';
                        }
                        showToast("Warning: Face ID is already registered for this employee.", "error");
                    }
                }
            } catch (error) {
                console.error("Validation check error:", error);
            }
        }

        // Capture Face Logic
        async function captureFace() {
            if (isEmployeeRegistered) {
                showToast("Cannot capture: This employee is already registered.", "error");
                return;
            }

            const employeeId = document.getElementById('employee_id').value;
            if (!employeeId) {
                showToast("Please select an employee first.", "info");
                return;
            }

            const detection = await faceapi.detectSingleFace(video)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {
                showToast("No face detected! Please adjust position.", "error");
                return;
            }

            currentDescriptor = Array.from(detection.descriptor);

            // Freeze Frame to Preview Image
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            previewImg.src = canvas.toDataURL('image/jpeg');
            previewImg.style.display = 'block';

            // Hide webcam view & guidance
            video.style.display = 'none';
            overlayCanvas.style.display = 'none';
            guidanceOverlay.style.display = 'none';

            // Button visibility updates
            btnCapture.style.display = 'none';
            btnRemove.style.display = 'flex';
            btnSave.style.display = 'flex';

            showToast("Face captured successfully! Preview below.", "success");
        }

        // Reset Frame
        function resetCapture() {
            currentDescriptor = null;
            previewImg.src = '';
            previewImg.style.display = 'none';

            video.style.display = 'block';
            overlayCanvas.style.display = 'block';
            guidanceOverlay.style.display = 'flex';

            btnCapture.style.display = 'flex';
            btnRemove.style.display = 'none';
            btnSave.style.display = 'none';

            validateEmployeeSelection();
        }

        // Save Registration
        async function saveRegistration() {
            if (isEmployeeRegistered) {
                showToast("Action blocked: Employee already registered.", "error");
                return;
            }

            const employeeSelect = document.getElementById('employee_id');
            const employeeId = employeeSelect.value;

            if (!employeeId || !currentDescriptor) {
                showToast("Missing employee selection or face descriptor.", "error");
                return;
            }

            btnSave.disabled = true;

            try {
                const response = await fetch("{{ route('face.register.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        face_descriptor: currentDescriptor
                    })
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    showToast(data.message || 'Registration successful!', "success");

                    // Drop registered employee option from list
                    const selectedOption = employeeSelect.querySelector(`option[value="${employeeId}"]`);
                    if (selectedOption) selectedOption.remove();

                    employeeSelect.value = '';
                    resetCapture();
                } else {
                    showToast(data.message || 'Registration rejected by server.', "error");
                }
            } catch (error) {
                console.error("Registration error:", error);
                showToast(error.message || "An unexpected error occurred.", "error");
            } finally {
                btnSave.disabled = false;
            }
        }
    </script>
</body>

</html>