<?php
session_start();

// Redirect logged-in users
if (isset($_SESSION["user"])) {
    header("Location: shop.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UrbanPulse - Discover the latest trends in fashion with bags, hoodies, and more.">
    <title>UrbanPulse - Trendy Fashion</title>
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff6200;
            --secondary: #1a252f;
            --accent: #00a8e8;
            --light: #f8f9fa;
            --white: #ffffff;
            --text: #212529;
            --border: #dee2e6;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        header {
            background-color: var(--secondary);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .navbar {
            padding: 0.5rem 1rem;
        }

        .navbar-brand img {
            height: 35px;
        }

        .navbar-nav .nav-link {
            color: var(--white);
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: var(--primary);
        }

        .navbar .btn {
            font-size: 0.9rem;
            padding: 0.4rem 1rem;
            color: var(--white);
            border-color: var(--primary);
        }

        .navbar .btn:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        /* Navbar Toggler */
        .navbar-toggler {
            border: 1px solid var(--primary);
            padding: 0.25rem 0.5rem;
            transition: all 0.2s ease;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%23ff6200' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .navbar-collapse {
            background-color: var(--secondary);
            padding: 1rem;
            transition: height 0.3s ease;
        }

        @media (max-width: 767.98px) {
            .navbar-collapse {
                border-top: 1px solid var(--border);
            }
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
            .navbar .btn {
                margin: 0.5rem 1rem;
                width: calc(100% - 2rem);
                text-align: center;
            }
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('/api/placeholder/1600/600');
            background-size: cover;
            background-position: center;
            min-height: 500px;
            color: var(--white);
            display: flex;
            align-items: center;
            text-align: center;
            padding: 2rem;
        }

        .hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .hero .btn {
            background-color: var(--primary);
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .hero .btn:hover {
            background-color: #e55a00;
        }

        @media (max-width: 767.98px) {
            .hero {
                min-height: 400px;
            }
            .hero h1 {
                font-size: 1.8rem;
            }
            .hero p {
                font-size: 1rem;
            }
        }

        /* Product Section */
        .product-section {
            padding: 3rem 0;
        }

        .product-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: var(--white);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .product-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .product-card .card-body {
            padding: 1rem;
            text-align: center;
        }

        .product-card .card-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .product-card .card-text {
            font-size: 0.9rem;
            color: var(--text);
        }

        .product-card .price {
            font-size: 1rem;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .product-card .btn {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        /* Footer */
        footer {
            background-color: var(--secondary);
            color: var(--white);
            padding: 2rem 0;
            text-align: center;
            margin-top: auto;
        }

        footer a {
            color: var(--primary);
            text-decoration: none;
            margin: 0 0.5rem;
        }

        footer a:hover {
            text-decoration: underline;
        }

        footer .social-icons a {
            font-size: 1.2rem;
            margin: 0 0.75rem;
            color: var(--white);
            transition: color 0.3s ease;
        }

        footer .social-icons a:hover {
            color: var(--primary);
        }

        /* Back to Top Button */
        #back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary);
            color: var(--white);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: none;
            z-index: 1000;
            box-shadow: var(--shadow);
            border: none;
            cursor: pointer;
        }

        #back-to-top:hover {
            background: #e55a00;
        }

        /* Loader */
        .loader {
            border: 4px solid rgba(255,255,255,0.2);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loader" id="loader"></div>

    <!-- Header -->
    <header>
        <nav class="navbar navbar-expand-md">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="/api/placeholder/120/35" alt="UrbanPulse Logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="about.php"><i class="fas fa-info-circle me-1"></i> About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
                        </li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="login.php" class="btn btn-outline-primary"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        <a href="register.php" class="btn btn-outline-primary"><i class="fas fa-user-plus me-1"></i> Sign Up</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Explore the Latest Fashion Trends</h1>
            <p>Discover stylish bags, hoodies, and accessories at UrbanPulse.</p>
            <a href="shop.php" class="btn">Shop Now</a>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="product-section">
        <div class="container">
            <h2 class="text-center mb-4">Featured Products</h2>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                <div class="col">
                    <div class="card product-card">
                        <img src="https://th.bing.com/th/id/R.28e207b477f86e4ddefcd2ad4ed95866?rik=eNKk4VablzELiw&riu=http%3a%2f%2fbensbargains.net%2fthecheckout%2fwp-content%2fuploads%2f2013%2f07%2fbags_herschel.jpg&ehk=hwKRmF%2bs5Fm9StVQ4eoizRB7f1OQJrw2R5XU4aUN7ho%3d&risl=&pid=ImgRaw&r=0" class="card-img-top" alt="Bag with 2 Extra Pockets">
                        <div class="card-body">
                            <h3 class="card-title">Stylish Bag</h3>
                            <p class="card-text">Bag with 2 Extra Pockets</p>
                            <p class="price">KSH 1,200</p>
                            <a href="shop.php" class="btn btn-outline-primary">Add to Cart</a>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card product-card">
                        <img src="https://media.endclothing.com/media/f_auto,q_auto:eco/prodmedia/media/catalog/product/0/6/06-11-2018_stussy_smoothstockappliquehoody_black_118285-blac_blr_1.jpg" class="card-img-top" alt="Black Color Stylish Hoodie">
                        <div class="card-body">
                            <h3 class="card-title">Black Hoodie</h3>
                            <p class="card-text">Stylish Black Hoodie</p>
                            <p class="price">KSH 1,500</p>
                            <a href="shop.php" class="btn btn-outline-primary">Add to Cart</a>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card product-card">
                        <img src="https://th.bing.com/th/id/OIP.KHK-xwnN0PQVqITIiN7wQQHaFu?rs=1&pid=ImgDetMain" class="card-img-top" alt="Durable Ceramic Mug">
                        <div class="card-body">
                            <h3 class="card-title">Ceramic Mug</h3>
                            <p class="card-text">Durable Ceramic Mug</p>
                            <p class="price">KSH 200</p>
                            <a href="shop.php" class="btn btn-outline-primary">Add to Cart</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>© <?php echo date("Y"); ?> UrbanPulse. All rights reserved.</p>
            <div class="mt-2">
                <a href="terms.php">Terms</a> |
                <a href="privacy.php">Privacy</a> |
                <a href="contact.php">Contact</a>
            </div>
            <div class="social-icons mt-3">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // Loader management
        const loader = document.getElementById("loader");
        window.addEventListener("load", () => {
            loader.style.display = "none";
        });
        setTimeout(() => {
            loader.style.display = "none";
        }, 5000);

        // Back to top button
        const backToTopButton = document.getElementById("back-to-top");
        window.addEventListener("scroll", () => {
            backToTopButton.style.display = window.scrollY > 300 ? "block" : "none";
        });
        backToTopButton.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        // Optional: Debug script to log navbar toggle events (uncomment to use)
        /*
        document.querySelector(".navbar-toggler").addEventListener("click", () => {
            console.log("Toggler clicked. Navbar collapse show:", document.querySelector(".navbar-collapse").classList.contains("show"));
        });
        */
    </script>
</body>
</html>