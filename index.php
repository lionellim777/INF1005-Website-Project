<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment 2 Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link rel="stylesheet" href="css/main.css">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    />
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php
        include "inc/nav.inc.php";
    ?>

    
    <div id="carouselExampleCaptions" class="carousel slide carousel-fade mb-4" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active"> 
                <img src="assets/phone.jpg" class="d-block w-100" alt="Phone">
                <div class="carousel-caption d-none d-md-block">
                    <h5>To Inspire</h5>
                    <p>“Let’s go invent tomorrow instead of worrying about what happened yesterday.” – Steve Jobs</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="assets/phone-berries.jpg" class="d-block w-100" alt="Phone-berries">
                <div class="carousel-caption d-none d-md-block">
                    <h5>To Innovate</h5>
                    <p>“Innovation is the outcome of a habit, not a random act.” – Sukant Ratnakar</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="assets/phone-blue.jpg" class="d-block w-100" alt="Phone-blue">
                <div class="carousel-caption d-none d-md-block">
                    <h5>To Commemorate</h5>
                    <p>“Technology is best when it brings people together.” – Matt Mullenweg</p>
                </div>
            </div>
        </div>
        <!-- <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button> -->
    </div>
        
    <div class="animated container my-5">
        <div class="text-center py-4">
            <h1 class="fw-bold">Our Collections</h1>
            <p class="text-muted">Discover the latest technological trends and keep up to date</p>
        </div>

        <div class="row justify-content-center g-4">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="card w-100">
                    <img src="assets/guinevere.jpg" class="card-img-top" alt="Guinevere">
                    <div class="card-body text-center">
                        <h5 class="card-title fw-bold">Guinevere</h5>
                        <p class="card-text">Revolutionary technology. Unlocking the impossible. Game changer.
                        </p>
                        <a href="catalog.php?category=<?= urlencode('Guinevere') ?>" class="card-btn btn btn-dark btn-sm w-100 view-product">
                            View Collection
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="card w-100">
                    <img src="assets/thamuz.jpg" class="card-img-top" alt="Thamuz">
                    <div class="card-body text-center">
                        <h5 class="card-title fw-bold">Thamuz</h5>
                        <p class="card-text">Seamless experience. Empowering your digital world. Built for you.
                        </p>
                        <a href="catalog.php?category=<?= urlencode('Thamuz') ?>" class="card-btn btn btn-dark btn-sm w-100 view-product">
                            View Collection
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="card w-100">
                    <img src="assets/esmeralda.jpg" class="card-img-top" alt="Esmeralda">
                    <div class="card-body text-center">
                        <h5 class="card-title fw-bold">Esmeralda</h5>
                        <p class="card-text">Unrivalled performance. Proven reliability. Trusted expertise.
                        </p>
                        <a href="catalog.php?category=<?= urlencode('Esmeralda') ?>" class="card-btn btn btn-dark btn-sm w-100 view-product">
                            View Collection
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center g-4 mt-2">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="card w-100">
                    <img src="assets/sora.jpg" class="card-img-top" alt="Sora">
                    <div class="card-body text-center">
                        <h5 class="card-title fw-bold">Sora</h5>
                        <p class="card-text">Accelerate your workflow. Reinventing value. Get more out of now.
                        </p>
                        <a href="catalog.php?category=<?= urlencode('Sora') ?>" class="card-btn btn btn-dark btn-sm w-100 view-product">
                            View Collection
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                <div class="card w-100">
                    <img src="assets/argus.jpg" class="card-img-top" alt="Argus">
                    <div class="card-body text-center">
                        <h5 class="card-title fw-bold">Argus</h5>
                        <p class="card-text">The power to be your best. Do what you can't. Effortless connectivity.
                        </p>
                        <a href="catalog.php?category=<?= urlencode('Argus') ?>" class="card-btn btn btn-dark btn-sm w-100 view-product">
                            View Collection
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <section class="position-relative py-5 animated">
        <img id="contact-map" src="assets/contact-map-2.png" alt="contact-map"
            class="position-absolute top-0 start-0 w-100 h-100">
        <div id="contact-container" class="container position-relative">
            <div class="row justify-content-end">
                <div class="col-md-6">
                    <div class="bg-white rounded p-4">
                        <h4 class="fw-bold mb-3">Contact Us</h4>
                        <form>
                            <div class="mb-3"><input type="text" class="form-control" placeholder="Your name"></div>
                            <div class="mb-3"><input type="email" class="form-control" placeholder="Your email"></div>
                            <div class="mb-3"><textarea class="form-control" placeholder="Your message" rows="3"></textarea></div>
                            <button id="contact-submit"type="submit" class="btn w-100 text-white">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    

    <?php
        include "inc/footer.inc.php";
    ?>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm"
        crossorigin="anonymous">
    </script>
    <script defer src="js/main.js"></script>
</body>
</html>
