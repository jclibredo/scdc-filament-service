<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincere Construction & Development Corporation | 真诚建设</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@500;700&family=Playfair+Display:ital,wght@0,500;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/webpage.css') }}">
    <style>
        /* Fade effect for Card Images */
        .card-carousel-img {
            transition: opacity 0.8s ease-in-out;
            opacity: 1;
        }

        .card-carousel-img.fade-out {
            opacity: 0;
        }

        /* Fade effect for Modal Carousel Image */
        #carouselSlide {
            transition: opacity 0.8s ease-in-out;
            opacity: 1;
        }

        #carouselSlide.fade-out {
            opacity: 0;
        }

        .card-img-container {
            position: relative;
            inline-size: 100%;
            block-size: 200px;
            background-color: #f1f5f9;
            overflow: hidden;
        }

        .fallback-icon-container {
            inline-size: 100%;
            block-size: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #2d2380;
            /* Deep Sapphire Blue matching Filament theme */
        }

        .project-fallback-icon {
            inline-size: 64px;
            block-size: 64px;
            stroke: #ffffff;
            opacity: 0.85;
        }
    </style>
</head>

<body>

    <!-- RESPONSIVE STICKY HEADER -->
    <header class="site-header">
        <a href="#" class="header-brand">
            <img src="{{ asset('images/scdc.jpg') }}" alt="SCDC Logo" class="logo-img">
            <div class="brand-text">
                <h1>Sincere Construction <span class="cn-title">真诚建设</span></h1>
                <p>Plumbing & Fire Protection</p>
            </div>
        </a>

        <!-- HAMBURGER BUTTON (MOBILE) -->
        <button class="hamburger-btn" id="menuToggle" onclick="toggleMenu()" aria-label="Toggle Navigation">
            ☰
        </button>

        <!-- NAVIGATION LINKS WITH ASSETS TAB -->
        <ul class="nav-links" id="navLinks">
            <li><a href="#home" onclick="closeMenu()">Home</a></li>
            <li><a href="#about" onclick="closeMenu()">About Us</a></li>
            <li><a href="#expertise" onclick="closeMenu()">Our Expertise</a></li>
            <li><a href="#assets" onclick="closeMenu()">Assets</a></li>
            <li><a href="#projects" onclick="closeMenu()">Our Projects</a></li>
            <li><a href="#contact" onclick="closeMenu()">Contact Us</a></li>
            <li><a href="#events" onclick="closeMenu()">Events</a></li>
        </ul>
    </header>

    <!-- CLEARER PAGE WRAPPER -->
    <div class="page-wrapper">
        <!-- 1. HERO / HOME SECTION -->
        <section id="home" class="hero-section">
            <div class="hero-left">
                <div class="hero-logo-box">
                    <img src="{{ asset('images/scdc.jpg') }}" alt="SCDC Corporate Logo" class="hero-logo-img">
                    <div class="hero-logo-text">
                        <h3>Sincere Construction</h3>
                        <span>真诚建设与发展公司</span>
                    </div>
                </div>

                <h1 class="hero-title">{{ $home->title ?? 'Engineering Defined' }}</h1>

                <div class="hero-thumbs">
                    @if(!empty($home->smallimage) && is_array($home->smallimage))
                    @foreach($home->smallimage as $thumb)
                    <img src="{{ Storage::url($thumb) }}" alt="Hero Thumbnail" class="hero-thumb-img">
                    @endforeach
                    @else
                    <img src="{{ asset('images/sanitary-plumbing.jpg') }}" alt="Sanitary Plumbing Fixtures" class="hero-thumb-img">
                    <img src="{{ asset('images/water-control-valves.jpg') }}" alt="Water Control Valves" class="hero-thumb-img">
                    @endif
                </div>

                <p class="hero-subtext">
                    {{ $home->description ?? 'Where commercial plumbing networks and automatic fire protection systems are engineered with precision.' }}
                </p>

                <a href="#contact" class="btn-primary">Learn More</a>
            </div>

            <div class="hero-right">
                @if($home->bigimage)
                <img src="{{ Storage::url($home->bigimage) }}" alt="Water Pipes and Industrial Valves Setup" class="hero-main-img">
                @else
                <img src="{{ asset('images/water-pipes-industrial-valves.jpg') }}" alt="Water Pipes and Industrial Valves Setup" class="hero-main-img">
                @endif
            </div>
        </section>

        <!-- ABOUT SECTION -->
        <section id="about">
            @php
            // Resolve cover image URL from storage with Unsplash fallback
            $aboutBanner = ($about && $about->coverimage)
            ? Storage::url($about->coverimage)
            : 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1600&q=80';
            @endphp

            <div class="about-section">
                <h2 class="about-title">About SCDC</h2>
                <div class="cn-subtitle">关于真诚建设</div>
                <h3 class="about-subtitle">Our Corporate Story</h3>

                <!-- Company Details -->
                <p class="about-description">
                    {!! nl2br(e($about->company_details ?? 'At Sincere Construction and Development Corporation, we take pride in delivering top-tier mechanical, sanitary, and life safety solutions for commercial, industrial, and high-end developments.')) !!}
                </p>

                <!-- Mission & Vision Cards (Optional extra structure if data exists) -->
                @if(!empty($about->mission) || !empty($about->vision))
                <div class="mission-vision-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 1.5rem 0;">
                    @if(!empty($about->mission))
                    <div class="mv-card" style="background: #f8fafc; padding: 1rem; border-inline-start: 4px solid #2d2380; border-radius: 0.375rem;">
                        <h4 style="font-weight: bold; margin-block-end: 0.5rem; color: #2d2380;">Our Mission</h4>
                        <p style="font-size: 0.9rem; color: #475569;">{!! nl2br(e($about->mission)) !!}</p>
                    </div>
                    @endif

                    @if(!empty($about->vision))
                    <div class="mv-card" style="background: #f8fafc; padding: 1rem; border-inline-start: 4px solid #2d2380; border-radius: 0.375rem;">
                        <h4 style="font-weight: bold; margin-block-end: 0.5rem; color: #2d2380;">Our Vision</h4>
                        <p style="font-size: 0.9rem; color: #475569;">{!! nl2br(e($about->vision)) !!}</p>
                    </div>
                    @endif
                </div>
                @endif

                <a href="#expertise" class="btn-primary">Discover More</a>
            </div>

            <!-- Banner Image with Fallback handling -->
            <img src="{{ $aboutBanner }}"
                alt="Industrial Pipe Lines & Valve Assembly"
                class="about-banner"
                onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1600&q=80';">
        </section>
        <!-- OUR EXPERTISE SECTION -->
        <section id="expertise" class="services-section">
            <h2 class="section-center-title">Our Expertise</h2>
            <p class="section-subtitle-text">Specialized engineering services tailored to high-density commercial developments and industrial facilities.</p>

            <div class="interactive-grid">
                @forelse($expertises as $expertise)
                @php
                // Image URL resolution
                $imageUrl = $expertise->image ? Storage::url($expertise->image) : '';

                // Format details for safe inline JavaScript passing (convert line breaks to <br>)
                $cleanDetails = str_replace(["\r\n", "\r", "\n"], '<br>', $expertise->details ?? '');
                @endphp

                <div class="interactive-card" onclick='openModal(
                "{{ $imageUrl }}",
                "{{ addslashes($expertise->title) }}",
                "Core Competency",
                "{{ addslashes($cleanDetails) }}"
            )'>
                    <div class="card-img-container">
                        <span class="card-badge">Engineering</span>

                        @if($imageUrl)
                        <img src="{{ $imageUrl }}"
                            alt="{{ $expertise->title }}"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                        <!-- Fallback icon container (hidden by default) -->
                        <div class="fallback-icon-container" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="project-fallback-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        @else
                        <!-- Direct fallback icon container if image path is missing -->
                        <div class="fallback-icon-container" style="display: flex;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="project-fallback-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        @endif
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">{{ $expertise->title }}</h3>
                        <p class="card-desc">{{ Str::limit($expertise->details, 100) }}</p>
                        <div class="card-action">View Specs &rarr;</div>
                    </div>
                </div>
                @empty
                <p style="text-align: center; grid-column: 1 / -1;">No expertise items listed at the moment.</p>
                @endforelse
            </div>
        </section>
        <!-- COMPANY ASSETS SECTION -->
        <section id="assets" class="assets-section">
            <h2 class="section-center-title">Company Assets</h2>
            <p class="section-subtitle-text">Our state-of-the-art power tools, specialized machinery, and dedicated transport fleet ensure rapid and reliable project execution.</p>

            <div class="interactive-grid">
                @forelse($assets as $asset)
                @php
                // Map select option key to user-friendly label
                $categoryLabel = match($asset->category) {
                'TOOLS' => 'Power Tools',
                'VEHICLE' => 'Vehicles',
                default => $asset->category,
                };

                // Handle image URL resolution with a fallback
                $imageUrl = $asset->image
                ? Storage::url($asset->image)
                : asset('images/default-asset.jpg');
                @endphp

                <div class="interactive-card" onclick="openModal(
                '{{ $imageUrl }}',
                '{{ e($asset->name) }}',
                '{{ e($categoryLabel) }}',
                '{{ e($asset->details) }}'
            )">
                    <div class="card-img-container">
                        <span class="card-badge">{{ $categoryLabel }}</span>
                        <img src="{{ $imageUrl }}" alt="{{ $asset->name }}">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">{{ $asset->name }}</h3>
                        <p class="card-desc">{{ $asset->details }}</p>
                        <div class="card-action">View {{ $categoryLabel }} &rarr;</div>
                    </div>
                </div>
                @empty
                <p style="text-align: center; grid-column: 1 / -1;">No company assets available at the moment.</p>
                @endforelse
            </div>
        </section>
        <!-- EVENTS SECTION -->
        <section id="events" class="assets-section">
            <h2 class="section-center-title">Company Events</h2>
            <p class="section-subtitle-text">Our state-of-the-art power tools, specialized machinery, and dedicated transport fleet ensure rapid and reliable project execution.</p>

            <div class="interactive-grid">
                @forelse($events as $event)
                @php
                // Resolve image paths to URLs
                $imageUrls = collect($event->image ?? [])->map(fn($path) => Storage::url($path))->values()->toArray();

                if (empty($imageUrls)) {
                $imageUrls = [asset('images/scdc.jpg')];
                }

                $coverImage = $imageUrls[0];
                $formattedDate = \Carbon\Carbon::parse($event->date)->format('M d, Y');

                // Format details cleanly for JavaScript string literals (convert line breaks to <br>)
                $cleanDetails = str_replace(["\r\n", "\r", "\n"], '<br>', $event->details ?? '');
                @endphp

                <div class="interactive-card event-auto-card"
                    data-images="{{ e(json_encode($imageUrls)) }}"
                    onclick='openModal(
                     {{ json_encode($imageUrls) }},
                     "{{ addslashes($event->title) }}",
                     "{{ $formattedDate }}",
                     "{{ addslashes($cleanDetails) }}"
                 )'>
                    <div class="card-img-container">
                        <span class="card-badge">{{ $formattedDate }}</span>
                        <!-- Auto-changing card image -->
                        <img src="{{ $coverImage }}" alt="{{ $event->title }}" class="card-carousel-img">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">{{ $event->title }}</h3>
                        <p class="card-desc">{{ $event->details }}</p>
                        <div class="card-action">View Event Details &rarr;</div>
                    </div>
                </div>
                @empty
                <p style="text-align: center; grid-column: 1 / -1;">No active events found.</p>
                @endforelse
            </div>
        </section>
        <!-- TESTIMONIALS SECTION -->
        <section class="testimonials-section">
            <h2 class="testimonials-header serif-title">Client Approvals</h2>

            <div class="testimonial-row">
                <div class="testimonial-author">Samantha Johnson (Project Director)</div>
                <div class="testimonial-quote">"Sincere Construction completed our high-rise fire protection system well within schedule. Their strict adherence to safety standards was commendable."</div>
            </div>

            <div class="testimonial-row">
                <div class="testimonial-author">Carlos Fernandez (Facility Manager)</div>
                <div class="testimonial-quote">"The engineering team at SCDC executed our entire commercial water supply layout flawlessly. Excellent coordination and top-notch valve workmanship."</div>
            </div>

            <div class="testimonial-row">
                <div class="testimonial-author">Priya Patel (Lead Architect)</div>
                <div class="testimonial-quote">"From technical planning to site installation, Sincere Construction exceeded our expectations in providing clean, efficient MEPF solutions."</div>
            </div>
        </section>

        <!-- OUR PROJECTS SECTION -->
        <section id="projects" class="projects-section">
            <h2 class="section-center-title">Our Projects</h2>
            <p class="section-subtitle-text">Explore recent commercial plumbing contracts and automatic fire protection deployments completed by Sincere Construction and Development Corporation.</p>

            <div class="interactive-grid">
                @forelse($projects as $project)
                @php
                // Status logic: true = NOT COMPLETED, false = COMPLETED
                $statusText = $project->status ? 'NOT COMPLETED' : 'COMPLETED';
                $badgeClass = $project->status ? 'badge-warning' : 'badge-success';

                // Image URL resolution
                $imageUrl = $project->image ? Storage::url($project->image) : '';

                // Escape double quotes and convert newlines into HTML breaks or safe JS spacing
                $details = "Scope: " . ($project->scope ?? 'N/A') . "<br>"
                . "Date Covered: " . ($project->datecovered ?? 'N/A') . "<br>"
                . "Location: " . ($project->address ?? 'N/A') . "<br>"
                . "Project Code: " . ($project->project_code ?? 'N/A');
                @endphp

                <div class="interactive-card" onclick="openModal(
                '{{ $imageUrl }}',
                '{{ addslashes($project->name) }}',
                '{{ $statusText }}',
                '{{ addslashes($details) }}'
            )">
                    <div class="card-img-container">
                        <span class="card-badge {{ $badgeClass }}">{{ $statusText }}</span>

                        @if($imageUrl)
                        <img src="{{ $imageUrl }}"
                            alt="{{ $project->name }}"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                        <!-- Fallback project icon container hidden by default -->
                        <div class="fallback-icon-container" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="project-fallback-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H7m4 0v10" />
                            </svg>
                        </div>
                        @else
                        <!-- Direct fallback icon container if image path is missing -->
                        <div class="fallback-icon-container" style="display: flex;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="project-fallback-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H7m4 0v10" />
                            </svg>
                        </div>
                        @endif
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">{{ $project->name }}</h3>
                        <p class="card-desc">
                            <strong>Scope:</strong> {{ $project->scope ?? 'N/A' }}<br>
                            <strong>Location:</strong> {{ $project->address ?? 'N/A' }}
                        </p>
                        <div class="card-action">View Details &rarr;</div>
                    </div>
                </div>
                @empty
                <p style="text-align: center; grid-column: 1 / -1;">No projects listed at the moment.</p>
                @endforelse
            </div>
        </section>

        <!-- CONTACT US SECTION -->
        <section id="contact" class="contact-section">
            <h2 class="section-center-title">Contact Us</h2>
            <p class="section-subtitle-text">Get in touch with our engineering team for inquiries, consultations, or project estimates.</p>

            <div class="contact-container">
                <div class="contact-info">
                    <h3>Sincere Construction & Development Corporation</h3>
                    <p><strong>Address:</strong> Head Office, Metro Manila, Philippines</p>
                    <p><strong>Email:</strong> info@sincereconstruction.com</p>
                    <p><strong>Phone:</strong> +63 (2) 8123-4567</p>
                    <p><strong>Hours:</strong> Mon - Sat: 8:00 AM - 5:00 PM</p>
                </div>

                <form class="contact-form" onsubmit="event.preventDefault(); alert('Thank you for contacting SCDC. We will get back to you shortly.');">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" placeholder="Your Full Name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" placeholder="name@company.com" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Project Details / Message</label>
                        <textarea id="message" rows="4" placeholder="How can SCDC assist with your project?" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="align-self: flex-start; border: none;">Send Message</button>
                </form>
            </div>
        </section>

    </div>


    <!-- RESPONSIVE POPUP MODAL -->
    <div id="infoModal" class="modal-backdrop" onclick="closeModalOnBackdrop(event)">
        <div class="modal-card">
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>

            <!-- CAROUSEL CONTAINER -->
            <div id="modalCarousel" class="modal-carousel" style="display: none; position: relative; inline-size: 100%; block-size: 300px; background: #000; overflow: hidden; border-radius: 0.5rem 0.5rem 0 0;">
                <img id="carouselSlide" src="" alt="Event Image" style="inline-size: 100%; block-size: 100%; object-fit: contain;">

                <!-- CONTROLS -->
                <button type="button" onclick="changeSlide(-1)" class="carousel-btn prev-btn" style="position: absolute; inset-block-start: 50%; inset-inline-start: 10px; transform: translateY(-50%); background: rgba(0,0,0,0.6); color: #fff; border: none; padding: 8px 12px; cursor: pointer; border-radius: 50%; font-weight: bold; font-size: 18px;">&#10094;</button>
                <button type="button" onclick="changeSlide(1)" class="carousel-btn next-btn" style="position: absolute; inset-block-start: 50%; inset-inline-end: 10px; transform: translateY(-50%); background: rgba(0,0,0,0.6); color: #fff; border: none; padding: 8px 12px; cursor: pointer; border-radius: 50%; font-weight: bold; font-size: 18px;">&#10095;</button>

                <!-- INDICATOR COUNTER -->
                <span id="carouselCounter" style="position: absolute; inset-block-end: 10px; inset-inline-end: 10px; background: rgba(0,0,0,0.7); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px;">1 / 1</span>
            </div>

            <!-- SINGLE IMAGE FALLBACK -->
            <img id="modalImg" src="" alt="Modal Image" class="modal-img" style="inline-size: 100%; block-size: 300px; object-fit: cover;">

            <div class="modal-body">
                <span id="modalCategory" class="modal-category">Category</span>
                <h2 id="modalTitle" class="modal-title">Title Here</h2>
                <p id="modalDetails" class="modal-details">Detailed description goes here...</p>
                <button class="btn-primary" onclick="closeModal()">Close Window</button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT FOR NAVIGATION & MODAL -->
    <script>
        // TOGGLE MOBILE MENU
        function toggleMenu() {
            const nav = document.getElementById('navLinks');
            const btn = document.getElementById('menuToggle');
            nav.classList.toggle('mobile-open');

            if (nav.classList.contains('mobile-open')) {
                btn.innerHTML = '✕';
            } else {
                btn.innerHTML = '☰';
            }
        }

        // CLOSE MOBILE MENU ON LINK CLICK
        function closeMenu() {
            const nav = document.getElementById('navLinks');
            const btn = document.getElementById('menuToggle');
            if (nav.classList.contains('mobile-open')) {
                nav.classList.remove('mobile-open');
                btn.innerHTML = '☰';
            }
        }

        let currentImages = [];
        let currentIndex = 0;
        let modalInterval = null;

        // SLOW FADING AUTO-ROTATE FOR CARDS ON GRID
        document.addEventListener('DOMContentLoaded', () => {
            const eventCards = document.querySelectorAll('.event-auto-card');

            eventCards.forEach(card => {
                const images = JSON.parse(card.getAttribute('data-images') || '[]');
                const imgElement = card.querySelector('.card-carousel-img');

                if (images.length > 1 && imgElement) {
                    let cardIndex = 0;
                    setInterval(() => {
                        // Step 1: Fade out
                        imgElement.classList.add('fade-out');

                        // Step 2: Swap image after fade completes, then fade back in
                        setTimeout(() => {
                            cardIndex = (cardIndex + 1) % images.length;
                            imgElement.src = images[cardIndex];
                            imgElement.classList.remove('fade-out');
                        }, 800); // 800ms matches the CSS transition time
                    }, 4000); // Swaps image every 4 seconds
                }
            });
        });

        function openModal(imgInput, title, category, details) {
            const modal = document.getElementById('infoModal');
            const singleImg = document.getElementById('modalImg');
            const carousel = document.getElementById('modalCarousel');

            // Clear any previous interval running in the modal
            if (modalInterval) clearInterval(modalInterval);

            // Normalize input parameter into an array
            if (Array.isArray(imgInput)) {
                currentImages = imgInput;
            } else if (typeof imgInput === 'string' && imgInput.trim() !== '') {
                currentImages = [imgInput];
            } else {
                currentImages = ['/images/default-project.jpg'];
            }

            currentIndex = 0;

            // Multi-Image Carousel Logic (Events)
            if (currentImages.length > 1 && carousel) {
                if (singleImg) singleImg.style.display = 'none';
                carousel.style.display = 'block';

                updateSlide();

                // Start auto-fading carousel interval
                modalInterval = setInterval(() => {
                    changeSlide(1);
                }, 4000);
            }
            // Single Image Logic (Projects / Assets)
            else {
                if (carousel) carousel.style.display = 'none';
                if (singleImg) {
                    singleImg.style.display = 'block';
                    singleImg.src = currentImages[0] || '/images/default-project.jpg';
                    singleImg.onerror = function() {
                        this.src = '/images/default-project.jpg';
                    };
                }
            }

            // Populate Content
            const titleEl = document.getElementById('modalTitle');
            const categoryEl = document.getElementById('modalCategory');
            const detailsEl = document.getElementById('modalDetails');

            if (titleEl) titleEl.textContent = title;
            if (categoryEl) categoryEl.textContent = category;
            if (detailsEl) detailsEl.innerHTML = details;

            // Display Modal
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function changeSlide(direction) {
            if (currentImages.length <= 1) return;

            const slide = document.getElementById('carouselSlide');
            if (slide) slide.classList.add('fade-out');

            setTimeout(() => {
                currentIndex = (currentIndex + direction + currentImages.length) % currentImages.length;
                updateSlide();
                if (slide) slide.classList.remove('fade-out');
            }, 800);
        }

        function updateSlide() {
            const slide = document.getElementById('carouselSlide');
            const counter = document.getElementById('carouselCounter');

            if (slide) slide.src = currentImages[currentIndex];
            if (counter) counter.textContent = `${currentIndex + 1} / ${currentImages.length}`;
        }

        function closeModal() {
            if (modalInterval) clearInterval(modalInterval);
            const modal = document.getElementById('infoModal');
            if (modal) modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function closeModalOnBackdrop(e) {
            if (e.target.id === 'infoModal') {
                closeModal();
            }
        }

        // function openModal(imgSrc, title, category, details) {
        //     const singleImg = document.getElementById('modalImg');
        //     const galleryContainer = document.getElementById('modalGallery');

        //     // Convert string inputs or array inputs into a unified array
        //     let images = Array.isArray(imgSrc) ? imgSrc : [imgSrc];

        //     if (images.length > 1) {
        //         // Render multiple images inside gallery container
        //         singleImg.style.display = 'none';
        //         galleryContainer.style.display = 'flex';
        //         galleryContainer.innerHTML = images.map(src => `
        //     <img src="${src}" alt="${title}" style="max-block-size: 250px; inline-size: auto; object-fit: cover; border-radius: 0.5rem;">
        // `).join('');
        //     } else {
        //         // Display single image as default
        //         if (galleryContainer) galleryContainer.style.display = 'none';
        //         singleImg.style.display = 'block';
        //         singleImg.src = images[0] || '';
        //     }

        //     document.getElementById('modalTitle').textContent = title;
        //     document.getElementById('modalCategory').textContent = category;
        //     document.getElementById('modalDetails').textContent = details;

        //     // Show modal
        //     document.getElementById('infoModal').classList.add('active');
        //     document.body.style.overflow = 'hidden';
        // }



        // function closeModal() {
        //     document.getElementById('infoModal').classList.remove('active');
        //     document.body.style.overflow = 'auto';
        // }

        // function closeModalOnBackdrop(e) {
        //     if (e.target.id === 'infoModal') {
        //         closeModal();
        //     }
        // }
    </script>

</body>

</html>