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

    <style>
        :root {
            /* Chinese Imperial Color Palette */
            --imperial-red: #b91c1c;      /* Primary Red */
            --dark-red: #7f1d1d;          /* Deep Crimson */
            --gold-accent: #d97706;        /* Imperial Gold */
            --gold-light: #f59e0b;       /* Bright Gold */
            --porcelain-bg: rgba(250, 246, 240, 0.88); /* Semi-Transparent Warm Silk Ivory */
            --card-bg: rgba(255, 255, 255, 0.85);      /* Semi-Transparent White Cards */
            --text-dark: #1c1917;
            --text-muted: #57534e;
            --border-gold: rgba(217, 119, 6, 0.3);
            --radius-md: 10px;
            --radius-lg: 16px;
            --header-height: 80px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            background: linear-gradient(135deg, rgba(24, 9, 9, 0.92), rgba(69, 10, 10, 0.88)), 
                        url('https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1920&q=80') center/cover no-repeat fixed;
            min-height: 100vh;
            padding-top: calc(var(--header-height) + 16px);
            padding-bottom: 24px;
            padding-left: 12px;
            padding-right: 12px;
        }

        /* ------------------------------------
           RESPONSIVE STICKY HEADER (TRANSPARENT GLASS)
        ------------------------------------ */
        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--header-height);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 clamp(16px, 4vw, 60px);
            background: rgba(250, 246, 240, 0.82); /* Semi-transparent Header */
            border-bottom: 2px solid var(--gold-accent);
            z-index: 1000;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .logo-img {
            height: clamp(38px, 5vw, 48px);
            width: auto;
            object-fit: contain;
            border-radius: 6px;
            border: 1px solid var(--border-gold);
        }

        .brand-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(0.95rem, 2vw, 1.2rem);
            font-weight: 700;
            color: var(--dark-red);
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .brand-text p {
            font-size: clamp(0.6rem, 1.2vw, 0.75rem);
            color: var(--gold-accent);
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .cn-title {
            font-family: 'Noto Serif SC', serif;
            font-size: 0.85rem;
            color: var(--imperial-red);
            font-weight: 700;
            margin-left: 4px;
        }

        /* DESKTOP NAV LINKS */
        .nav-links {
            display: flex;
            gap: clamp(14px, 2vw, 32px);
            list-style: none;
            align-items: center;
            transition: all 0.3s ease;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.2s;
            white-space: nowrap;
        }

        .nav-links a:hover {
            color: var(--imperial-red);
        }

        /* MOBILE HAMBURGER BUTTON */
        .hamburger-btn {
            display: none;
            background: rgba(250, 246, 240, 0.6);
            border: 1px solid var(--gold-accent);
            color: var(--dark-red);
            font-size: 1.6rem;
            width: 42px;
            height: 42px;
            border-radius: 6px;
            cursor: pointer;
            justify-content: center;
            align-items: center;
            transition: all 0.2s ease;
        }

        .hamburger-btn:hover {
            background: rgba(217, 119, 6, 0.15);
            color: var(--imperial-red);
        }

        /* ------------------------------------
           PAGE WRAPPER (SEMI-TRANSPARENT GLASS)
        ------------------------------------ */
        .page-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--porcelain-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-gold);
        }

        /* Typography */
        h1, h2, h3, .serif-title {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            color: var(--text-dark);
        }

        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, var(--imperial-red), var(--dark-red));
            color: #ffffff;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid var(--gold-accent);
            cursor: pointer;
            text-align: center;
            box-shadow: 0 4px 12px rgba(185, 28, 28, 0.25);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-red), #500707);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(185, 28, 28, 0.4);
            border-color: var(--gold-light);
        }

        /* ------------------------------------
           1. HERO SECTION
        ------------------------------------ */
        .hero-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            min-height: 540px;
        }

        .hero-left {
            padding: clamp(30px, 6vw, 70px) clamp(20px, 5vw, 60px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
        }

        .hero-logo-box {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            border: 2px solid var(--gold-accent);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(217, 119, 6, 0.15);
        }

        .hero-logo-img {
            height: 52px;
            width: auto;
            object-fit: contain;
        }

        .hero-logo-text h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--dark-red);
            line-height: 1.1;
        }

        .hero-logo-text span {
            font-family: 'Noto Serif SC', serif;
            font-size: 0.75rem;
            color: var(--gold-accent);
            font-weight: 700;
        }

        .hero-title {
            font-size: clamp(2.2rem, 5vw, 3.4rem);
            line-height: 1.15;
            margin-bottom: 24px;
            color: var(--dark-red);
        }

        .hero-thumbs {
            display: flex;
            gap: 16px;
            margin-bottom: 28px;
            width: 100%;
        }

        .hero-thumb-img {
            width: clamp(100px, 20vw, 140px);
            height: clamp(120px, 22vw, 160px);
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--gold-accent);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .hero-subtext {
            font-size: clamp(0.85rem, 1.5vw, 0.95rem);
            color: var(--text-muted);
            max-width: 420px;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .hero-right {
            position: relative;
            width: 100%;
            min-height: 340px;
            border-left: 2px solid var(--gold-accent);
        }

        .hero-main-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ------------------------------------
           2. ABOUT SECTION
        ------------------------------------ */
        .about-section {
            padding: clamp(40px, 6vw, 80px) clamp(20px, 5vw, 60px) 40px;
            text-align: center;
            max-width: 850px;
            margin: 0 auto;
        }

        .about-title {
            font-size: clamp(2rem, 4vw, 2.8rem);
            color: var(--dark-red);
            margin-bottom: 8px;
        }

        .cn-subtitle {
            font-family: 'Noto Serif SC', serif;
            color: var(--gold-accent);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: 2px;
        }

        .about-subtitle {
            font-size: clamp(0.9rem, 1.5vw, 1rem);
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--text-dark);
        }

        .about-description {
            font-size: clamp(0.85rem, 1.3vw, 0.92rem);
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 32px;
        }

        .about-banner {
            width: 100%;
            height: clamp(240px, 40vw, 460px);
            object-fit: cover;
            margin-top: 20px;
            border-top: 2px solid var(--gold-accent);
            border-bottom: 2px solid var(--gold-accent);
        }

        /* ------------------------------------
           3. EXPERTISE, ASSETS & PROJECTS GRID
        ------------------------------------ */
        .services-section, .assets-section, .projects-section {
            padding: clamp(40px, 6vw, 80px) clamp(20px, 5vw, 60px);
            border-top: 1px solid var(--border-gold);
        }

        .section-center-title {
            text-align: center;
            font-size: clamp(2rem, 4vw, 2.8rem);
            color: var(--dark-red);
            margin-bottom: clamp(10px, 2vw, 16px);
        }

        .section-subtitle-text {
            text-align: center;
            font-size: clamp(0.85rem, 1.3vw, 0.95rem);
            color: var(--text-muted);
            max-width: 650px;
            margin: 0 auto clamp(30px, 5vw, 50px);
            line-height: 1.6;
        }

        .interactive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: clamp(20px, 3vw, 32px);
        }

        .interactive-card {
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-gold);
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex;
            flex-direction: column;
        }

        .interactive-card:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 18px 36px rgba(185, 28, 28, 0.2);
            border-color: var(--imperial-red);
        }

        .card-img-container {
            width: 100%;
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .interactive-card:hover .card-img-container img {
            transform: scale(1.08);
        }

        .card-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: linear-gradient(135deg, var(--imperial-red), var(--dark-red));
            color: white;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            border: 1px solid var(--gold-light);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .card-content {
            padding: 22px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-red);
            transition: color 0.2s;
        }

        .interactive-card:hover .card-title {
            color: var(--imperial-red);
        }

        .card-desc {
            font-size: 0.86rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .card-action {
            margin-top: auto;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gold-accent);
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ------------------------------------
           4. TESTIMONIALS SECTION
        ------------------------------------ */
        .testimonials-section {
            border-top: 1px solid var(--border-gold);
            background: rgba(243, 237, 226, 0.45);
        }

        .testimonials-header {
            padding: clamp(24px, 4vw, 40px) clamp(20px, 5vw, 60px);
            font-size: clamp(1.8rem, 3.5vw, 2.5rem);
            color: var(--dark-red);
            border-bottom: 1px solid var(--border-gold);
        }

        .testimonial-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            padding: clamp(20px, 4vw, 40px) clamp(20px, 5vw, 60px);
            border-bottom: 1px solid var(--border-gold);
            gap: 16px;
            align-items: center;
        }

        .testimonial-row:last-child {
            border-bottom: none;
        }

        .testimonial-author {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--dark-red);
        }

        .testimonial-quote {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.6;
            font-style: italic;
        }

        /* ------------------------------------
           RESPONSIVE POPUP MODAL
        ------------------------------------ */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(28, 9, 9, 0.85);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 16px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            width: 100%;
            max-width: 700px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 2px solid var(--gold-accent);
            transform: scale(0.92);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
        }

        .modal-backdrop.active .modal-card {
            transform: scale(1);
        }

        .modal-close-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--imperial-red);
            color: white;
            border: 1px solid var(--gold-light);
            font-size: 1.3rem;
            cursor: pointer;
            z-index: 10;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background 0.2s;
        }

        .modal-close-btn:hover {
            background: var(--dark-red);
        }

        .modal-img {
            width: 100%;
            height: clamp(180px, 35vh, 320px);
            object-fit: cover;
            flex-shrink: 0;
        }

        .modal-body {
            padding: clamp(20px, 4vw, 32px);
            overflow-y: auto;
        }

        .modal-category {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--gold-accent);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .modal-title {
            font-size: clamp(1.4rem, 3vw, 1.8rem);
            color: var(--dark-red);
            margin-bottom: 12px;
        }

        .modal-details {
            font-size: clamp(0.85rem, 1.2vw, 0.95rem);
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 20px;
        }

        /* ------------------------------------
           MOBILE BREAKPOINTS (HAMBURGER MENU)
        ------------------------------------ */
        @media (max-width: 860px) {
            .hamburger-btn {
                display: flex;
            }

            .nav-links {
                position: absolute;
                top: var(--header-height);
                left: 0;
                width: 100%;
                background: rgba(250, 246, 240, 0.92);
                backdrop-filter: blur(16px);
                flex-direction: column;
                align-items: stretch;
                padding: 0;
                max-height: 0;
                overflow: hidden;
                border-bottom: 0px solid var(--gold-accent);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            }

            .nav-links.mobile-open {
                max-height: 380px;
                padding: 16px 0;
                border-bottom: 2px solid var(--gold-accent);
            }

            .nav-links li {
                width: 100%;
                text-align: center;
            }

            .nav-links a {
                display: block;
                padding: 12px 20px;
                font-size: 1rem;
                border-bottom: 1px solid rgba(217, 119, 6, 0.1);
            }

            .nav-links li:last-child a {
                border-bottom: none;
            }

            .hero-section {
                grid-template-columns: 1fr;
            }

            .hero-right {
                height: 280px;
                border-left: none;
                border-top: 2px solid var(--gold-accent);
            }
        }
    </style>
</head>
<body>

    <!-- RESPONSIVE STICKY HEADER (TRANSPARENT) -->
    <header class="site-header">
        <a href="#" class="header-brand">
            <img src="{{ asset('images/scdc.jpg') }}" alt="SCDC Logo" class="logo-img">
            <div class="brand-text">
                <h1>Sincere Construction <span class="cn-title">真诚建设</span></h1>
                <p>Plumbing & Fire Protection</p>
            </div>
        </a>

        <!-- HAMBURGER BUTTON (MOBILE ONLY) -->
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

    <!-- PAGE WRAPPER (TRANSPARENT GLASS) -->
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

        <!-- ASSETS & EQUIPMENT SECTION -->
        <section id="assets" class="assets-section">
            <h2 class="section-center-title">Company Assets</h2>
            <p class="section-subtitle-text">Our state-of-the-art power tools, specialized machinery, and dedicated transport fleet ensure rapid and reliable project execution.</p>

            <div class="interactive-grid">
                <!-- Asset Card 1: Power Tools -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1000&q=80',
                    'Heavy-Duty Power Tools & Machinery',
                    'Equipment & Tools',
                    'Our inventory includes industrial electric pipe threaders, hydraulic roll groovers, electro-hydraulic press tools, fused welding machinery, and high-precision core drills for heavy steel and copper piping.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Machinery</span>
                        <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=600&q=80" alt="Heavy Power Tools">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Industrial Power Tools</h3>
                        <p class="card-desc">Electric pipe threaders, hydraulic groovers, and electro-hydraulic press tools.</p>
                        <div class="card-action">View Inventory &rarr;</div>
                    </div>
                </div>

                <!-- Asset Card 2: Transport Vehicles -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=1000&q=80',
                    'Fleet & Transport Vehicles',
                    'Logistics Fleet',
                    'A dedicated fleet of heavy utility trucks, material hauling vans, and emergency response vehicles ensuring seamless logistics, prompt material delivery, and rapid field deployment across job sites.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Vehicles</span>
                        <img src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80" alt="Fleet and Transport Vehicles">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Fleet & Logistics Vehicles</h3>
                        <p class="card-desc">Material hauling trucks and mobile site service vans for rapid field deployment.</p>
                        <div class="card-action">View Fleet &rarr;</div>
                    </div>
                </div>

                <!-- Asset Card 3: Hydrostatic Testing Rig -->
                <div class="interactive-card" onclick="openModal(
                    'https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&w=1000&q=80',
                    'Hydrostatic & Calibration Rigs',
                    'Testing Gear',
                    'Portable diesel-powered hydrostatic pressure test pumps, digital pressure logging units, ultrasonic flow meters, and certified backflow test kits for strict quality assurance.'
                )">
                    <div class="card-img-container">
                        <span class="card-badge">Testing Rig</span>
                        <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&w=600&q=80" alt="Hydrostatic Testing Equipment">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Hydrostatic Testing Rigs</h3>
                        <p class="card-desc">High-pressure testing rigs and calibrated digital gauges for pipeline compliance.</p>
                        <div class="card-action">View Details &rarr;</div>
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