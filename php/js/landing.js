document.addEventListener("DOMContentLoaded", function() {
    // Intersection Observer for animations
    const observerOptions = { threshold: 0.1 };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, observerOptions);
    
    const targets = document.querySelectorAll('.cat-item, .seasonal-card, .t-card');
    targets.forEach(target => {
        target.classList.add('reveal');
        observer.observe(target);
    });
    
    // Hover effect on shop button
    document.querySelectorAll('.btn-black').forEach(shopBtn => {
        shopBtn.addEventListener('mousemove', (e) => {
            const rect = shopBtn.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const move = (x - rect.width / 2) / 10;
            shopBtn.style.transform = `rotate(${move}deg) scale(1.05)`;
        });
        shopBtn.addEventListener('mouseleave', () => {
            shopBtn.style.transform = 'rotate(0deg) scale(1)';
        });
    });

    // Slider functionality
    let currentSlide = 0;
    let autoPlay;
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.slider-arrow.prev');
    const nextBtn = document.querySelector('.slider-arrow.next');
    const slider = document.getElementById('hero-slider');
    const sliderContainer = document.querySelector('.slider-container');

    function showSlide(index) {
        sliderContainer.style.transform = `translateX(-${index * 100}%)`;
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        currentSlide = index;
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    }

    function startAutoPlay() {
        autoPlay = setInterval(nextSlide, 5000);
    }

    function resetAutoPlay() {
        clearInterval(autoPlay);
        startAutoPlay();
    }

    // Arrow button navigation with auto-play reset
    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetAutoPlay();
        });
        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetAutoPlay();
        });
    }

    // Dot navigation with auto-play reset
    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            showSlide(i);
            resetAutoPlay();
        });
    });

    // Start auto-play
    startAutoPlay();

    // Pause on hover, resume on leave
    if (slider) {
        slider.addEventListener('mouseenter', () => clearInterval(autoPlay));
        slider.addEventListener('mouseleave', () => startAutoPlay());
    }
});