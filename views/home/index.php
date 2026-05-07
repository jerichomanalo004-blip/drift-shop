<!-- Hero Slider -->
<section id="hero-slider">
    <div class="slider-container">
        <div class="slide active">
            <img src="/shop/shirts/Hero_Banner_Extension_A_young_man_with_dark_curly_hair_wears_sunglasses_zsG2EWn5.png" alt="Slide 1">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h1>Define Your Style</h1>
                <p>Bold Streetwear Inspired By Real Culture — Crafted For Comfort, Movement, and Everyday Confidence.</p>
                <div class="hero-actions">
                    <a href="<?= $shop_link ?>" class="btn-black">Shop The Drop</a>
                </div>
            </div>
        </div>
        <div class="slide">
            <img src="/shop/shirts/Hero_Banner_Design_A_young_person_sits_slumped_on_old_stone_steps_rmVt-1ul.png" alt="Slide 2">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h1>Bold Streetwear</h1>
                <p>Inspired By Real Culture — Crafted For Comfort, Movement, and Everyday Confidence.</p>
                <div class="hero-actions">
                    <a href="<?= $shop_link ?>" class="btn-black">Shop The Drop</a>
                </div>
            </div>
        </div>
        <div class="slide">
            <img src="/shop/shirts/Hero_Banner_Extension_A_young_man_stands_with_his_hands_in_his_pockets_eTNAVt6B.png" alt="Slide 3">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h1>Crafted For Comfort</h1>
                <p>Bold Streetwear Inspired By Real Culture — Movement, and Everyday Confidence.</p>
                <div class="hero-actions">
                    <a href="<?= $shop_link ?>" class="btn-black">Shop The Drop</a>
                </div>
            </div>
        </div>
    </div>
    <button class="slider-arrow prev">‹</button>
    <button class="slider-arrow next">›</button>
    <div class="slider-dots">
        <span class="dot active"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>
</section>

<!-- Clothesline Categories Section -->
<section id="categories" class="clothesline-categories">
    <div class="section-header">
        <h2>Shop the Collection</h2>
        <p>From Everyday Essentials To Statement Pieces—Find Your Fit.</p>
    </div>
    <div class="clothesline-wrapper">
        <div class="clothesline-rope"></div>
        <div class="clothesline-items">
            <a href="store.php?cat=tees" class="hanging-category">
                <div class="hanging-inner">
                    <img src="../shirts/CHARLOTTE%20FOLK%20LFDM%20TEE/CF-LFDMTEE.png" alt="T-Shirts">
                    <span class="category-label">T-SHIRTS</span>
                </div>
            </a>
            <a href="store.php?cat=hoodies" class="hanging-category">
                <div class="hanging-inner">
                    <img src="../shirts/1777364808_Artboard_49.jpg" alt="Hoodies">
                    <span class="category-label">HOODIES</span>
                </div>
            </a>
            <a href="store.php?cat=jackets" class="hanging-category">
                <div class="hanging-inner">
                    <img src="../shirts/CAMPUS%20CORE%20LEATHER%20JACKET%20-%20Brown/Screenshot%202026-04-30%20214918.png" alt="Jackets">
                    <span class="category-label">JACKETS</span>
                </div>
            </a>
            <a href="store.php?cat=sweat" class="hanging-category">
                <div class="hanging-inner">
                    <img src="../shirts/HIGH%20Name%20Bar%20Sweatshirt/T6_48544ce1-b03e-451c-b466-0a028f7c3371.webp" alt="Sweatshirts">
                    <span class="category-label">SWEATSHIRTS</span>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Seasonal Collection -->
<section id="seasonal" class="seasonal-container">
    <div class="section-header">
        <h2>Seasonal Collection</h2>
        <p>Every Season Brings A New Drop—Bold Designs, Clean Fits, And Statement Pieces Made For The Streets</p>
    </div>
    <div class="seasonal-flex">
        <div class="seasonal-card">
            <img src="../shirts/DBTK START UP TEE - OFF WHITE/DBTK START UP TEE - OFF WHITE5.jpg" alt="Cooperate Collection">
            <div class="seasonal-overlay">
                <button class="pill-btn">PREPPY STYLE</button>
            </div>
        </div>
        <div class="seasonal-card">
            <img src="../shirts/DBTK SPARK NATURE CAMO TEE - WHITE-CAMO/DBTK SPARK NATURE CAMO TEE - WHITE_CAMO7.jpg" alt="Streetwear Collection">
            <div class="seasonal-overlay">
                <button class="pill-btn">STREET WEAR</button>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="testimonials-container">
    <div class="testimonial-header">
        <div class="header-left">
            <h2>Fashion That Speaks For Itself</h2>
            <p>Discover Fashion That Goes Beyond Trends—Pieces Designed To Express Your Personality, Elevate Your Everyday Style, And Make A Statement Wherever You Go</p>
        </div>
        <div class="header-right">
            <a href="#" class="see-all">See All ↗</a>
        </div>
    </div>

    <div class="testimonial-grid">
        <?php if (!empty($testimonials)): ?>
            <?php foreach ($testimonials as $row): ?>
                <div class="t-card">
                    <span style="font-size: 16px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; color: #5a5959;">
                        Item: <?= htmlspecialchars($row['product_name']) ?>
                    </span>
                    <div class="stars" style="color: #FFD700; margin-top: 5px;">
                        <?= str_repeat('★', (int)$row['rating']) ?>
                    </div>
                    <p>"<?= htmlspecialchars($row['comment']) ?>"</p>
                    <div class="user-info">
                        <span><?= htmlspecialchars($row['first_name']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No testimonials yet. Be the first to review!</p>
        <?php endif; ?>
    </div>
</section>