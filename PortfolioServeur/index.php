<?php
require_once 'config.php';

try {
  // Connexion avec PDO
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
  // Activation des erreurs pour faciliter le débogage
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Requête pour récupérer tous les projets du plus récent au plus ancien
  $stmt = $pdo->query("SELECT * FROM projets ORDER BY ordre ASC");
  $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // Stoppe le script et affiche l'erreur en cas de problème de connexion
  die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
<!doctype html>
<html lang="fr" data-theme="dark">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mes Projets — Accueil</title>
  <meta name="description" content="Sélection de nos projets : Reservoir Rock, ASSTSF, Les Farfadets Vertigo." />
  <meta name="theme-color" content="#0b1329" />

  <!-- FlyonUI + Tailwind v4 (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flyonui@2/flyonui.css" />
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet" />

  <style type="text/tailwindcss">
    @theme {
      --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
      --font-serif: "Instrument Serif", ui-serif, Georgia, serif;
      --color-bg: #0b1329;
      --color-fg: #f3f4f6;
      --color-muted: #94a3b8;
      --color-line: #1e293b;
      --color-accent: #38bdf8;
    }
  </style>

  <style>
    html {
      scroll-behavior: smooth;
    }

    body {
      background: #0b1329;
      color: #f3f4f6;
      font-family: Inter, system-ui, sans-serif;
    }

    @media (pointer: fine) {

      body,
      a,
      button {
        cursor: none;
      }
    }

    /* ---------- Curseur ---------- */
    .cursor {
      position: fixed;
      top: 0;
      left: 0;
      z-index: 90;
      pointer-events: none;
      mix-blend-mode: difference;
    }

    .cursor-dot {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: #fff;
      translate: -50% -50%;
      display: grid;
      place-items: center;
      transition: width .35s cubic-bezier(.2, .8, .2, 1), height .35s cubic-bezier(.2, .8, .2, 1);
    }

    .cursor-dot span {
      font-size: 11px;
      font-weight: 500;
      color: #000;
      opacity: 0;
      transition: opacity .2s;
      letter-spacing: .05em;
    }

    .cursor.is-link .cursor-dot {
      width: 44px;
      height: 44px;
    }

    .cursor.is-view .cursor-dot {
      width: 84px;
      height: 84px;
    }

    .cursor.is-view .cursor-dot span {
      opacity: 1;
    }

    @media (pointer: coarse) {
      .cursor {
        display: none;
      }
    }

    /* ---------- Barre de progression ---------- */
    #progress {
      position: fixed;
      top: 0;
      left: 0;
      height: 2px;
      width: 100%;
      background: #38bdf8;
      z-index: 80;
      transform-origin: 0 50%;
      transform: scaleX(0);
    }

    /* ---------- Apparition au scroll ---------- */
    .reveal {
      opacity: 0;
      transform: translateY(28px);
      transition: opacity 1s cubic-bezier(.2, .8, .2, 1), transform 1s cubic-bezier(.2, .8, .2, 1);
      transition-delay: var(--d, 0s);
    }

    .reveal.in {
      opacity: 1;
      transform: none;
    }

    /* ---------- Soulignement animé ---------- */
    .u {
      background: linear-gradient(currentColor, currentColor) 0 100% / 0 1px no-repeat;
      transition: background-size .5s cubic-bezier(.2, .8, .2, 1);
    }

    .u:hover {
      background-size: 100% 1px;
    }

    /* ---------- Cartes projet ---------- */
    .project .shot {
      position: relative;
      aspect-ratio: 4 / 3;
      overflow: hidden;
      border-radius: 14px;
      background: #070d1d;
      clip-path: inset(12% 12% 12% 12% round 14px);
      transition: clip-path 1.3s cubic-bezier(.2, .8, .2, 1);
    }

    .project.in .shot {
      clip-path: inset(0 0 0 0 round 14px);
    }

    .shot iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 400%;
      height: 400%;
      border: 0;
      pointer-events: none;
      transform: scale(.25);
      transform-origin: 0 0;
      opacity: 0;
      transition: opacity .8s, transform 1.4s cubic-bezier(.2, .8, .2, 1);
    }

    .shot iframe.loaded {
      opacity: 1;
    }

    .project:hover .shot iframe.loaded {
      transform: scale(.27) translate(-3.5%, -3.5%);
    }

    .shot .ph {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      transition: opacity .8s;
    }

    .shot:has(iframe.loaded) .ph {
      opacity: 0;
    }

    .shot::after {
      content: "";
      position: absolute;
      inset: 0;
      border-radius: inherit;
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .1);
      pointer-events: none;
    }

    .project .title-row svg {
      transition: transform .5s cubic-bezier(.2, .8, .2, 1);
    }

    .project:hover .title-row svg {
      transform: rotate(45deg);
    }

    .project h3 {
      transition: letter-spacing .5s cubic-bezier(.2, .8, .2, 1);
    }

    .project:hover h3 {
      letter-spacing: -0.01em;
    }

    /* ---------- Shimmer placeholder ---------- */
    .shimmer {
      background: linear-gradient(110deg, #070d1d 30%, #111c38 50%, #070d1d 70%);
      background-size: 200% 100%;
      animation: shimmer 2.2s linear infinite;
    }

    @keyframes shimmer {
      to {
        background-position: -200% 0;
      }
    }

    /* ---------- Marquee ---------- */
    .marquee {
      mask-image: linear-gradient(90deg, transparent, #000 12%, #000 88%, transparent);
    }

    .marquee-track {
      display: flex;
      width: max-content;
      animation: marquee 35s linear infinite;
    }

    .marquee:hover .marquee-track {
      animation-play-state: paused;
    }

    @keyframes marquee {
      to {
        transform: translateX(-50%);
      }
    }

    .magnetic {
      transition: transform .4s cubic-bezier(.2, .8, .2, 1);
    }

    @media (prefers-reduced-motion: reduce) {

      *,
      *::before,
      *::after {
        animation: none !important;
        transition: none !important;
      }

      .reveal {
        opacity: 1 !important;
        transform: none !important;
      }

      .project .shot {
        clip-path: none !important;
      }
    }
  </style>
</head>

<body class="antialiased selection:bg-accent selection:text-black">

  <div id="progress"></div>
  <div class="cursor" aria-hidden="true">
    <div class="cursor-dot"><span>Voir</span></div>
  </div>

  <!-- Navigation -->
  <header class="fixed inset-x-0 top-0 z-50 border-b border-transparent transition-colors duration-500" id="nav">
    <nav class="navbar mx-auto max-w-6xl bg-transparent px-6 py-3 flex items-center justify-between">
      <div class="navbar-start">
        <a href="#projets" class="group flex items-center" aria-label="Accueil">
          <img src="/logo_orque.png" alt="Logo Orque & Banquise" class="size-16 rounded-full object-cover border-2 border-accent/40 transition-transform duration-300 group-hover:scale-105 shadow-md" />
        </a>
      </div>
      <div class="navbar-end gap-8 text-sm text-muted">
        <a href="#projets" class="u hover:text-fg transition-colors">Projets</a>
        <a href="#apropos" class="u hover:text-fg transition-colors max-sm:hidden">À propos</a>
        <span class="flex items-center gap-2 tabular-nums"><span class="relative flex size-1.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-accent opacity-60"></span><span class="relative inline-flex size-1.5 rounded-full bg-accent"></span></span><span id="clock">--:--</span> Six-Fours</span>
      </div>
    </nav>
  </header>

  <main id="top">
    <!-- PROJETS -->
    <section id="projets" class="mx-auto max-w-6xl scroll-mt-20 px-6 pb-24 pt-36">
      <div class="mb-14 flex items-end justify-between border-b border-line pb-6">
        <h2 class="reveal text-4xl font-medium tracking-tight md:text-5xl">Mes <em class="font-serif font-normal italic text-accent">projets</em> en ligne</h2>
        <span class="reveal text-sm tabular-nums text-muted" style="--d:.1s">(<?= str_pad(count($projets), 2, '0', STR_PAD_LEFT) ?>)</span>
      </div>

      <div class="grid gap-x-8 gap-y-16 md:grid-cols-3">

        <!-- Boucle PHP sur les projets -->
        <?php foreach ($projets as $index => $projet): ?>
          <a href="<?= htmlspecialchars($projet['lien_projet']) ?>" target="_blank" rel="noopener" data-cursor="view"
            data-path="<?= htmlspecialchars(parse_url($projet['lien_projet'], PHP_URL_PATH) ?? '') ?>" class="project reveal flex h-full flex-col" style="--d:.<?= $index + 1 ?>s">

            <div class="shot">
              <div class="ph shimmer"><span class="font-serif text-2xl italic text-muted"><?= htmlspecialchars($projet['titre']) ?></span></div>
              <iframe title="Aperçu <?= htmlspecialchars($projet['titre']) ?>" tabindex="-1" aria-hidden="true"
                data-src="<?= htmlspecialchars($projet['lien_projet']) ?>"></iframe>
            </div>

            <div class="title-row mt-6 flex items-center justify-between">
              <h3 class="text-xl font-medium tracking-tight"><?= htmlspecialchars($projet['titre']) ?></h3>
              <svg class="size-5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M7 17L17 7M8 7h9v9" />
              </svg>
            </div>

            <p class="mt-3 min-h-[4.5rem] text-sm leading-relaxed text-muted">
              <?= nl2br(htmlspecialchars($projet['description'])) ?>
            </p>

            <div class="mt-auto flex items-center justify-between border-t border-line pt-4 text-xs text-muted">
              <span>
                <?= htmlspecialchars($projet['technologie']) ?>
                <?php if ($projet['espace_admin'] == 1): ?> · Espace admin<?php endif; ?>
              </span>
              <!-- Remplacement de l'année par défaut, tu pourrais aussi l'ajouter dans ta BDD si tu veux -->
              <span class="flex items-center gap-2 tabular-nums text-muted"><span class="size-1.5 rounded-full bg-accent"></span>2026</span>
            </div>
          </a>
        <?php endforeach; ?>

      </div>
    </section>

    <!-- STACK -->
    <section class="border-y border-line py-10" aria-label="Technologies">
      <div class="marquee overflow-hidden">
        <div class="marquee-track text-2xl font-light tracking-tight text-muted md:text-3xl items-center">
          <!-- Première série -->
          <div class="flex shrink-0 items-center gap-14 pr-14">
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:nextjs-icon.svg" alt="Next.js" class="size-6 object-contain" /><span>Next.js</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:react.svg" alt="React" class="size-6" /><span>React</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:tailwindcss-icon.svg" alt="Tailwind CSS" class="size-6" /><span>Tailwind CSS</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3">
              <svg class="size-6 shrink-0" viewBox="0 0 64 64" fill="none">
                <defs>
                  <linearGradient id="auth-top" x1="8" y1="6" x2="56" y2="44" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#2dd4bf" />
                    <stop offset="100%" stop-color="#0ea5e9" />
                  </linearGradient>
                  <linearGradient id="auth-bot" x1="12" y1="20" x2="56" y2="60" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#e879f9" />
                    <stop offset="100%" stop-color="#7e22ce" />
                  </linearGradient>
                  <clipPath id="auth-shield">
                    <path d="M32 4 L56 12 V30 C56 46 32 58 32 60 C32 58 8 46 8 30 V12 L32 4 Z" />
                  </clipPath>
                </defs>
                <g clip-path="url(#auth-shield)">
                  <rect width="64" height="64" fill="url(#auth-bot)" />
                  <polygon points="0,0 64,0 64,12 0,46" fill="url(#auth-top)" />
                  <path d="M32 0 V64" stroke="#000" stroke-opacity="0.08" stroke-width="2" />
                </g>
                <circle cx="32" cy="31" r="14" fill="#f1f5f9" />
                <path d="M27.5 23 A5.5 5.5 0 1 0 33 28.5 L34.5 30 L34.5 32.5 L37 32.5 L37 35 L39.5 35 L39.5 37.5 L42.5 37.5 L42.5 33.5 L33 24 A5.5 5.5 0 0 0 27.5 23 Z" fill="#f59e0b" />
                <circle cx="26" cy="27" r="1.8" fill="#f1f5f9" />
              </svg>
              <span>Auth.js</span>
            </div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:prisma.svg" alt="Prisma" class="size-6" /><span>Prisma</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:nodejs-icon.svg" alt="Node.js" class="size-6" /><span>Node.js</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:mysql.svg" alt="MySQL" class="size-6" /><span>MySQL</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:apache.svg" alt="Apache" class="size-6" /><span>Apache</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:linux-tux.svg" alt="Linux" class="size-6" /><span>Linux</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:debian.svg" alt="Debian" class="size-6" /><span>Debian</span></div>
            <span class="text-accent">/</span>
          </div>

          <!-- Deuxième série (copie exacte) -->
          <div class="flex shrink-0 items-center gap-14 pr-14" aria-hidden="true">
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:nextjs-icon.svg" alt="Next.js" class="size-6 object-contain" /><span>Next.js</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:react.svg" alt="React" class="size-6" /><span>React</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:tailwindcss-icon.svg" alt="Tailwind CSS" class="size-6" /><span>Tailwind CSS</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3">
              <svg class="size-6 shrink-0" viewBox="0 0 64 64" fill="none">
                <g clip-path="url(#auth-shield)">
                  <rect width="64" height="64" fill="url(#auth-bot)" />
                  <polygon points="0,0 64,0 64,12 0,46" fill="url(#auth-top)" />
                  <path d="M32 0 V64" stroke="#000" stroke-opacity="0.08" stroke-width="2" />
                </g>
                <circle cx="32" cy="31" r="14" fill="#f1f5f9" />
                <path d="M27.5 23 A5.5 5.5 0 1 0 33 28.5 L34.5 30 L34.5 32.5 L37 32.5 L37 35 L39.5 35 L39.5 37.5 L42.5 37.5 L42.5 33.5 L33 24 A5.5 5.5 0 0 0 27.5 23 Z" fill="#f59e0b" />
                <circle cx="26" cy="27" r="1.8" fill="#f1f5f9" />
              </svg>
              <span>Auth.js</span>
            </div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:prisma.svg" alt="Prisma" class="size-6" /><span>Prisma</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:nodejs-icon.svg" alt="Node.js" class="size-6" /><span>Node.js</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:mysql.svg" alt="MySQL" class="size-6" /><span>MySQL</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:apache.svg" alt="Apache" class="size-6" /><span>Apache</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:linux-tux.svg" alt="Linux" class="size-6" /><span>Linux</span></div>
            <span class="text-accent">/</span>
            <div class="flex items-center gap-3"><img src="https://api.iconify.design/logos:debian.svg" alt="Debian" class="size-6" /><span>Debian</span></div>
            <span class="text-accent">/</span>
          </div>
        </div>
      </div>
    </section>

    <!-- À PROPOS -->
    <section id="apropos" class="mx-auto max-w-6xl scroll-mt-20 px-6 py-28">
      <div class="grid gap-12 md:grid-cols-12 items-center">
        <!-- Colonne Image -->
        <div class="reveal md:col-span-5" style="--d:.1s">
          <div class="relative aspect-square overflow-hidden rounded-[2rem] bg-[#070d1d] border border-line p-2 shadow-2xl">
            <img src="/logo_orque.png" alt="Orque et Banquise" class="w-full h-full object-cover rounded-[1.5rem] opacity-90 transition-transform duration-700 hover:scale-105" />
            <div class="absolute inset-0 rounded-[2rem] shadow-[inset_0_0_0_1px_rgba(255,255,255,0.05)] pointer-events-none"></div>
          </div>
        </div>

        <!-- Colonne Texte -->
        <div class="md:col-span-7 md:pl-10">
          <p class="reveal text-sm font-semibold tracking-widest text-accent uppercase mb-6">À propos</p>
          <h2 class="reveal text-3xl font-light leading-tight tracking-tight md:text-5xl md:leading-[1.15]">
            L'agilité en surface,<br> la <em class="font-serif italic text-accent">puissance</em> en profondeur.
          </h2>
          <p class="reveal mt-8 text-lg text-muted leading-relaxed" style="--d:.1s">
            À l'image de l'orque, je conçois des projets web fluides et rapides, soutenus par une infrastructure robuste et invisible en profondeur.
          </p>
          <p class="reveal mt-6 text-muted leading-relaxed" style="--d:.2s">
            J'accompagne les associations et vos projets indépendants en offrant une véritable liberté numérique : serveurs VPS Linux, configuration sur mesure (reverse proxy Apache, bases de données) et totale maîtrise des données.
          </p>
        </div>
      </div>
    </section>
  </main>

  <footer class="border-t border-line">
    <div class="mx-auto flex max-w-6xl flex-col gap-6 px-6 py-12 md:flex-row md:items-end md:justify-between">
      <p class="reveal font-serif text-2xl italic text-muted">Portail des projets</p>
      <div class="flex items-center gap-6 text-sm text-muted">
        <span>© <span id="year"></span></span>
        <a href="#projets" class="u magnetic hover:text-fg transition-colors">Haut de page ↑</a>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/flyonui@2/flyonui.js" defer></script>
  <script>
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const fine = matchMedia('(pointer: fine)').matches;
    document.getElementById('year').textContent = new Date().getFullYear();

    /* Horloge */
    const clock = document.getElementById('clock');
    const fmt = new Intl.DateTimeFormat('fr-FR', {
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'Europe/Paris'
    });
    const setClock = () => clock.textContent = fmt.format(new Date());
    setClock();
    setInterval(setClock, 10000);

    /* Apparition au scroll */
    const io = new IntersectionObserver(entries => entries.forEach(e => {
      if (!e.isIntersecting) return;
      e.target.classList.add('in');
      io.unobserve(e.target);
    }), {
      threshold: .2
    });
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));

    /* Barre de progression + nav */
    const bar = document.getElementById('progress'),
      nav = document.getElementById('nav');
    addEventListener('scroll', () => {
      const h = document.documentElement.scrollHeight - innerHeight;
      bar.style.transform = `scaleX(${h > 0 ? scrollY / h : 0})`;
      nav.classList.toggle('border-line', scrollY > 40);
      nav.classList.toggle('backdrop-blur-md', scrollY > 40);
      nav.classList.toggle('bg-[#0b1329]/80', scrollY > 40);
    }, {
      passive: true
    });

    /* Curseur personnalisé */
    if (fine) {
      const cur = document.querySelector('.cursor');
      let x = innerWidth / 2,
        y = innerHeight / 2,
        cx = x,
        cy = y;
      addEventListener('pointermove', e => {
        x = e.clientX;
        y = e.clientY;
      });
      (function loop() {
        cx += (x - cx) * (reduce ? 1 : .2);
        cy += (y - cy) * (reduce ? 1 : .2);
        cur.style.transform = `translate(${cx}px, ${cy}px)`;
        requestAnimationFrame(loop);
      })();
      document.querySelectorAll('a, button').forEach(el => {
        const cls = el.dataset.cursor === 'view' ? 'is-view' : 'is-link';
        el.addEventListener('pointerenter', () => cur.classList.add(cls));
        el.addEventListener('pointerleave', () => cur.classList.remove(cls));
      });
    }

    /* Aperçus live des projets (iframe) */
    const sameHost = location.hostname === 'vps-9a818ab2.vps.ovh.net';
    document.querySelectorAll('.project').forEach(card => {
      const iframe = card.querySelector('iframe');
      const load = () => {
        if (iframe.src) return;
        iframe.addEventListener('load', () => iframe.classList.add('loaded'), {
          once: true
        });
        iframe.src = iframe.dataset.src;
      };
      const whenVisible = () => new IntersectionObserver((en, o) => {
        if (en[0].isIntersecting) {
          load();
          o.disconnect();
        }
      }, {
        rootMargin: '300px'
      }).observe(card);

      if (!sameHost) return whenVisible();
      fetch(card.dataset.path, {
        cache: 'no-store'
      }).then(r => {
        if (r.ok) whenVisible();
      }).catch(whenVisible);
    });
  </script>
</body>

</html>