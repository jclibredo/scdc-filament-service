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
        </ul>
    </header>

    <!-- CLEARER PAGE WRAPPER -->
    <div class="page-wrapper">

        <!-- HERO SECTION -->
        <section id="home" class="hero-section">
            <div class="hero-left">

                <div class="hero-logo-box">
                    <img src="{{ asset('images/scdc.jpg') }}" alt="SCDC Corporate Logo" class="hero-logo-img">
                    <div class="hero-logo-text">
                        <h3>Sincere Construction</h3>
                        <span>真诚建设与发展公司</span>
                    </div>
                </div>

                <h1 class="hero-title">Engineering Defined</h1>
                <div class="hero-thumbs">
                    <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=300&q=80" alt="Sanitary Plumbing Fixtures" class="hero-thumb-img">
                    <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=300&q=80" alt="Water Control Valves" class="hero-thumb-img">
                </div>
                <p class="hero-subtext">Where commercial plumbing networks and automatic fire protection systems are engineered with precision, compliance, and lasting reliability.</p>
                <a href="#contact" class="btn-primary">Learn More</a>
            </div>

            <!-- WATER PIPES & VALVES HERO IMAGE -->
            <div class="hero-right">
                <img src="https://images.unsplash.com/photo-1542013936693-884638332954?auto=format&fit=crop&w=1200&q=80" alt="Water Pipes and Industrial Valves Setup" class="hero-main-img">
            </div>
        </section>

        <!-- ABOUT SECTION -->
        <section id="about">
            <div class="about-section">
                <h2 class="about-title">About SCDC</h2>
                <div class="cn-subtitle">关于真诚建设</div>
                <h3 class="about-subtitle">Our Corporate Story</h3>
                <p class="about-description">
                    At Sincere Construction and Development Corporation, we take pride in delivering top-tier mechanical, sanitary, and life safety solutions for commercial, industrial, and high-end developments. With a dedicated focus on custom piping layouts, water control valve installations, and strict NFPA compliance, our engineering craftsmanship guarantees excellence in every contract.
                </p>
                <a href="#expertise" class="btn-primary">Discover More</a>
            </div>
            <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1600&q=80" alt="Industrial Pipe Lines & Valve Assembly" class="about-banner">
        </section>

        <!-- OUR EXPERTISE SECTION -->
        <section id="expertise" class="services-section">
            <h2 class="section-center-title">Our Expertise</h2>
            <p class="section-subtitle-text">Specialized engineering services tailored to high-density commercial developments and industrial facilities.</p>

            <div class="interactive-grid">
                <!-- Card 1 -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1000&q=80',
                    'Sanitary & Domestic Plumbing',
                    'Sanitary Engineering',
                    'Complete engineered solutions for multi-level sanitary drainage, high-pressure water main connections, grease trap filtration, and commercial booster pump assemblies. Designed for high durability and strict architectural compliance.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Sanitary</span>
                        <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=600&q=80" alt="Custom Plumbing Systems">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Sanitary & Domestic Plumbing</h3>
                        <p class="card-desc">Complete engineered solutions for multi-level sanitary drainage, water mains, and booster pumps.</p>
                        <div class="card-action">View Specs &rarr;</div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&w=1000&q=80',
                    'Fire Protection Systems',
                    'Life Safety Engineering',
                    'Turnkey installation of automatic fire sprinklers, wet standpipe risers, electric fire pump controls, and backflow preventers meeting NFPA & National Building Code standards.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Fire Safety</span>
                        <img src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&w=600&q=80" alt="Fire Sprinkler System">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Fire Protection Systems</h3>
                        <p class="card-desc">Automatic fire sprinklers, standpipe risers, and certified fire pump installations.</p>
                        <div class="card-action">View Specs &rarr;</div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1000&q=80',
                    'Testing & Preventive Maintenance',
                    'Quality Assurance',
                    'Comprehensive hydrostatic line pressure testing, system flushing, gate valve audits, and routine preventive care to guarantee zero operational downtime for critical building piping.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Maintenance</span>
                        <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=600&q=80" alt="Piping Maintenance">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Testing & Preventive Care</h3>
                        <p class="card-desc">Hydrostatic line testing, valve audits, and scheduled preventive maintenance programs.</p>
                        <div class="card-action">View Specs &rarr;</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- COMPANY ASSETS SECTION (WITH SEPARATED CARDS FOR POWER TOOLS AND TRANSPORT VEHICLES) -->
        <section id="assets" class="assets-section">
            <h2 class="section-center-title">Company Assets</h2>
            <p class="section-subtitle-text">Our state-of-the-art power tools, specialized machinery, and dedicated transport fleet ensure rapid and reliable project execution.</p>

            <div class="interactive-grid">
                <!-- Asset Card 1: Power Tools -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1000&q=80',
                    'Heavy-Duty Site Power Tools',
                    'Power Tools & Equipment',
                    'Our heavy equipment inventory features high-torque electric pipe threaders, hydraulic roll groovers, electro-hydraulic press tools, electro-fusion welding machines, and diamond core drills designed for steel and copper piping.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Power Tools</span>
                        <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=600&q=80" alt="Industrial Power Tools">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Industrial Power Tools</h3>
                        <p class="card-desc">Electric pipe threaders, hydraulic groovers, and electro-hydraulic press tools for on-site efficiency.</p>
                        <div class="card-action">View Power Tools &rarr;</div>
                    </div>
                </div>

                <!-- Asset Card 2: Transport Vehicles -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=1000&q=80',
                    'Fleet & Transport Vehicles',
                    'Transport & Logistics',
                    'A company-owned transport fleet consisting of heavy utility trucks, material hauling vans, and mobile site service vehicles for rapid field deployment and safe material transport across job sites.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Vehicles</span>
                        <img src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80" alt="Fleet and Transport Vehicles">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Transport Vehicles</h3>
                        <p class="card-desc">Material hauling trucks, flatbeds, and mobile emergency service vans for rapid site deployment.</p>
                        <div class="card-action">View Fleet &rarr;</div>
                    </div>
                </div>

                <!-- Asset Card 3: Water Pipes & Control Valves -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1542013936693-884638332954?auto=format&fit=crop&w=1000&q=80',
                    'Water Pipes & Control Valves',
                    'Piping Systems',
                    'A complete array of heavy-duty duct iron piping, seamless carbon steel lines, brass butterfly valves, gate valves, and pressure regulating assemblies tailored for commercial water distribution networks.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Piping Assets</span>
                        <img src="https://images.unsplash.com/photo-1542013936693-884638332954?auto=format&fit=crop&w=600&q=80" alt="Water Pipes and Valves Inventory">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Water Pipes & Valves</h3>
                        <p class="card-desc">Ductile iron piping, high-pressure valves, and certified backflow control assemblies.</p>
                        <div class="card-action">View Catalog &rarr;</div>
                    </div>
                </div>

                <!-- Asset Card 4: Testing & Calibration Rig -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&w=1000&q=80',
                    'Hydrostatic Testing & Calibration Rigs',
                    'Testing Gear',
                    'Diesel-powered high-pressure hydrostatic testing rigs, calibrated digital logging gauges, and ultrasonic flow meters to guarantee leak-proof pipeline installation.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Testing Rig</span>
                        <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&w=600&q=80" alt="Hydrostatic Testing Equipment">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Hydrostatic Testing Rigs</h3>
                        <p class="card-desc">High-pressure testing rigs and calibrated digital gauges for strict pipeline compliance.</p>
                        <div class="card-action">View Specs &rarr;</div>
                    </div>
                </div>
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
                <!-- Project Card 1 -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1000&q=80',
                    'Commercial Water Distribution Hub',
                    'Infrastructure Contract',
                    'Designed and installed heavy-duty water pressure distribution manifolds and backflow prevention units for a multi-tenant commercial lifestyle center.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Completed</span>
                        <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=600&q=80" alt="Water Hub Project">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Water Distribution Hub</h3>
                        <p class="card-desc">Pressure distribution manifolds and commercial backflow units for a lifestyle center.</p>
                        <div class="card-action">View Details &rarr;</div>
                    </div>
                </div>

                <!-- Project Card 2 -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1542013936693-884638332954?auto=format&fit=crop&w=1000&q=80',
                    'High-Rise Fire Suppression Standpipe',
                    'Fire Protection Project',
                    'Complete execution of vertical wet standpipes, fire pump connections, and zone valve controls designed for automated alarm and central building management integration.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Completed</span>
                        <img src="https://images.unsplash.com/photo-1542013936693-884638332954?auto=format&fit=crop&w=600&q=80" alt="Fire Standpipe Project">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Fire Suppression Standpipe</h3>
                        <p class="card-desc">Vertical wet standpipe risers and high-capacity fire pump connections.</p>
                        <div class="card-action">View Details &rarr;</div>
                    </div>
                </div>

                <!-- Project Card 3 -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&w=1000&q=80',
                    'Industrial Water Pump & Main Line Fitting',
                    'Mechanical Contract',
                    'A specialized contract for high-volume water pump systems, pressure regulators, and precision control valve distribution networks for modern industrial facilities.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Completed</span>
                        <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&w=600&q=80" alt="Industrial Pump Project">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Industrial Pump Installation</h3>
                        <p class="card-desc">High-volume water pumps and precision valve networks for industrial plants.</p>
                        <div class="card-action">View Details &rarr;</div>
                    </div>
                </div>
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
            <img id="modalImg" src="" alt="Modal Image" class="modal-img">
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

        // MODAL FUNCTIONS
        function openModal(imgSrc, title, category, details) {
            document.getElementById('modalImg').src = imgSrc;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalCategory').textContent = category;
            document.getElementById('modalDetails').textContent = details;

            document.getElementById('infoModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('infoModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function closeModalOnBackdrop(e) {
            if (e.target.id === 'infoModal') {
                closeModal();
            }
        }
    </script>

</body>

</html>