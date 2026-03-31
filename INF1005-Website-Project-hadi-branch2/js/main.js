/* ============================================================
   Pomegranate – Main JavaScript
   Particles, typing effect, scroll animations, counters
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    // ── Dynamic year ─────────────────────────────────────────
    const yearEl = document.getElementById('year');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    // ── Particles canvas ─────────────────────────────────────
    initParticles();

    // ── Typed effect ─────────────────────────────────────────
    initTyped();

    // ── Scroll reveal ────────────────────────────────────────
    initScrollReveal();

    // ── Counter animation ────────────────────────────────────
    initCounters();

    // ── Navbar scroll style ──────────────────────────────────
    initNavbarScroll();

    // ── Product filter ───────────────────────────────────────
    initProductFilter();

    // ── Cart quantity controls ───────────────────────────────
    initCartControls();

    // ── Toast notifications ──────────────────────────────────
    initToasts();
});

/* ──────────────────────────────────────────────────────────── */
/*  PARTICLES                                                    */
/* ──────────────────────────────────────────────────────────── */
function initParticles() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let particles = [];
    let animId;
    let mouse = { x: null, y: null, radius: 120 };

    const COLORS = ['#22d3ee', '#818cf8', '#f472b6', '#34d399'];
    const COUNT  = window.innerWidth < 768 ? 55 : 100;

    function resize() {
        canvas.width  = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
    }

    class Particle {
        constructor() { this.reset(true); }

        reset(initial = false) {
            this.x  = Math.random() * canvas.width;
            this.y  = initial ? Math.random() * canvas.height : canvas.height + 10;
            this.vx = (Math.random() - .5) * .5;
            this.vy = -(Math.random() * .4 + .1);
            this.r  = Math.random() * 2 + 1;
            this.color = COLORS[Math.floor(Math.random() * COLORS.length)];
            this.alpha = Math.random() * .6 + .2;
            this.life  = 1;
        }

        update() {
            // Mouse repulsion
            if (mouse.x !== null) {
                const dx = this.x - mouse.x;
                const dy = this.y - mouse.y;
                const dist = Math.sqrt(dx*dx + dy*dy);
                if (dist < mouse.radius) {
                    const force = (mouse.radius - dist) / mouse.radius;
                    this.vx += dx / dist * force * .8;
                    this.vy += dy / dist * force * .8;
                }
            }

            // Dampen velocity
            this.vx *= .99;
            this.vy *= .99;

            this.x += this.vx;
            this.y += this.vy;

            // Wrap horizontal
            if (this.x < 0) this.x = canvas.width;
            if (this.x > canvas.width) this.x = 0;

            // Reset when out of top
            if (this.y < -10) this.reset();
        }

        draw() {
            ctx.save();
            ctx.globalAlpha = this.alpha;
            ctx.fillStyle   = this.color;
            ctx.shadowBlur  = 10;
            ctx.shadowColor = this.color;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    }

    function drawConnections() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx   = particles[i].x - particles[j].x;
                const dy   = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx*dx + dy*dy);
                const maxD = 100;
                if (dist < maxD) {
                    const alpha = (1 - dist / maxD) * .18;
                    ctx.save();
                    ctx.globalAlpha  = alpha;
                    ctx.strokeStyle  = '#22d3ee';
                    ctx.lineWidth    = .7;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                    ctx.restore();
                }
            }
        }
    }

    function loop() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawConnections();
        particles.forEach(p => { p.update(); p.draw(); });
        animId = requestAnimationFrame(loop);
    }

    function init() {
        resize();
        particles = Array.from({ length: COUNT }, () => new Particle());
        if (animId) cancelAnimationFrame(animId);
        loop();
    }

    // Event listeners
    window.addEventListener('resize', () => { resize(); });
    canvas.addEventListener('mousemove', e => {
        const rect = canvas.getBoundingClientRect();
        mouse.x = e.clientX - rect.left;
        mouse.y = e.clientY - rect.top;
    });
    canvas.addEventListener('mouseleave', () => { mouse.x = null; mouse.y = null; });

    init();
}

/* ──────────────────────────────────────────────────────────── */
/*  TYPING EFFECT                                               */
/* ──────────────────────────────────────────────────────────── */
function initTyped() {
    const el = document.getElementById('typed-text');
    if (!el) return;

    const phrases = ['Next-Gen Tech.', 'Your Future.', 'Limitless Power.', 'Pure Innovation.'];
    let phraseIdx = 0;
    let charIdx   = 0;
    let isDeleting = false;
    let pauseTime  = 0;

    function type() {
        const current = phrases[phraseIdx];

        if (isDeleting) {
            el.textContent = current.substring(0, charIdx - 1);
            charIdx--;
        } else {
            el.textContent = current.substring(0, charIdx + 1);
            charIdx++;
        }

        let speed = isDeleting ? 50 : 90;

        if (!isDeleting && charIdx === current.length) {
            speed = 1800;
            isDeleting = true;
        } else if (isDeleting && charIdx === 0) {
            isDeleting  = false;
            phraseIdx   = (phraseIdx + 1) % phrases.length;
            speed = 400;
        }

        setTimeout(type, speed);
    }

    setTimeout(type, 800);
}

/* ──────────────────────────────────────────────────────────── */
/*  SCROLL REVEAL                                               */
/* ──────────────────────────────────────────────────────────── */
function initScrollReveal() {
    const els = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    if (!els.length) return;

    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    els.forEach(el => obs.observe(el));
}

/* ──────────────────────────────────────────────────────────── */
/*  COUNTER ANIMATION                                           */
/* ──────────────────────────────────────────────────────────── */
function initCounters() {
    const counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            const el     = e.target;
            const target = parseFloat(el.dataset.count);
            const suffix = el.dataset.suffix || '';
            const prefix = el.dataset.prefix || '';
            const dur    = 1800;
            const start  = performance.now();

            function update(now) {
                const elapsed  = now - start;
                const progress = Math.min(elapsed / dur, 1);
                const ease     = 1 - Math.pow(1 - progress, 4); // ease-out-quart
                const val      = target * ease;
                el.textContent = prefix + (Number.isInteger(target)
                    ? Math.floor(val).toLocaleString()
                    : val.toFixed(1)) + suffix;
                if (progress < 1) requestAnimationFrame(update);
            }

            requestAnimationFrame(update);
            obs.unobserve(el);
        });
    }, { threshold: 0.5 });

    counters.forEach(c => obs.observe(c));
}

/* ──────────────────────────────────────────────────────────── */
/*  NAVBAR SCROLL                                               */
/* ──────────────────────────────────────────────────────────── */
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    function update() {
        if (window.scrollY > 60) {
            navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,.4)';
        } else {
            navbar.style.boxShadow = 'none';
        }
    }

    window.addEventListener('scroll', update, { passive: true });
}

/* ──────────────────────────────────────────────────────────── */
/*  PRODUCT FILTER (catalog page)                              */
/* ──────────────────────────────────────────────────────────── */
function initProductFilter() {
    const filterBtns   = document.querySelectorAll('.filter-btn[data-filter]');
    const productCards = document.querySelectorAll('.product-card-wrap[data-category]');
    const searchInput  = document.getElementById('product-search');
    if (!filterBtns.length) return;

    let activeFilter = 'all';
    let searchQuery  = '';

    function applyFilters() {
        productCards.forEach(card => {
            const cat   = card.dataset.category || '';
            const name  = (card.dataset.name || '').toLowerCase();
            const catOk = activeFilter === 'all' || cat === activeFilter;
            const srOk  = !searchQuery || name.includes(searchQuery);
            card.style.display = (catOk && srOk) ? '' : 'none';
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            searchQuery = searchInput.value.toLowerCase().trim();
            applyFilters();
        });
    }
}

/* ──────────────────────────────────────────────────────────── */
/*  CART CONTROLS                                               */
/* ──────────────────────────────────────────────────────────── */
function initCartControls() {
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const action    = this.dataset.action;
            const productId = this.dataset.id;
            const qtyEl     = document.getElementById(`qty-${productId}`);
            if (!qtyEl) return;

            const current = parseInt(qtyEl.textContent);
            let  newQty   = action === 'inc' ? current + 1 : current - 1;
            if (newQty < 0) newQty = 0;

            try {
                const res  = await fetch('cart_update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: newQty })
                });
                const data = await res.json();
                if (data.success) {
                    if (newQty === 0) {
                        document.getElementById(`cart-row-${productId}`)?.remove();
                    } else {
                        qtyEl.textContent = newQty;
                    }
                    updateCartTotal(data.total);
                    updateCartBadge(data.count);
                }
            } catch (e) {
                console.error(e);
            }
        });
    });
}

function updateCartTotal(total) {
    const el = document.getElementById('cart-total');
    if (el && total !== undefined) el.textContent = '$' + parseFloat(total).toFixed(2);
}

function updateCartBadge(count) {
    const badge = document.querySelector('.badge-dot');
    if (badge) badge.textContent = count || '';
}

/* ──────────────────────────────────────────────────────────── */
/*  TOAST NOTIFICATIONS                                         */
/* ──────────────────────────────────────────────────────────── */
function initToasts() {
    // Auto-dismiss alerts after 4s
    document.querySelectorAll('.auto-dismiss').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .5s ease';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });
}

/** Show a toast from anywhere in the page */
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container') || (() => {
        const c = document.createElement('div');
        c.id = 'toast-container';
        c.style.cssText = 'position:fixed;top:80px;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;';
        document.body.appendChild(c);
        return c;
    })();

    const colors = {
        success: 'rgba(52,211,153,.12)',
        error:   'rgba(248,113,113,.12)',
        info:    'rgba(34,211,238,.12)',
    };
    const borders = {
        success: 'rgba(52,211,153,.35)',
        error:   'rgba(248,113,113,.35)',
        info:    'rgba(34,211,238,.35)',
    };
    const icons = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };

    const toast = document.createElement('div');
    toast.style.cssText = `
        background:${colors[type]};border:1px solid ${borders[type]};
        color:#fff;padding:.75rem 1.1rem;border-radius:10px;
        backdrop-filter:blur(10px);min-width:260px;max-width:340px;
        display:flex;align-items:center;gap:.65rem;font-size:.88rem;font-weight:500;
        transform:translateX(110%);transition:transform .35s cubic-bezier(.4,0,.2,1);
        box-shadow:0 8px 24px rgba(0,0,0,.3);font-family:'Urbanist',sans-serif;
    `;
    toast.innerHTML = `<i class="bi ${icons[type]} flex-shrink-0"></i><span>${message}</span>`;
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(0)';
        setTimeout(() => {
            toast.style.transform = 'translateX(110%)';
            setTimeout(() => toast.remove(), 380);
        }, 3500);
    });
}

// Expose globally
window.showToast = showToast;
