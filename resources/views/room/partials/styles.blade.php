    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Pusher & Echo -->
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
            color: white;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* 3D Dice CSS */
        .scene {
            width: 120px;
            height: 120px;
            perspective: 800px;
            margin: 20px auto;
        }

        .dice {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .dice.rolling {
            animation: spinDice 0.5s linear infinite;
            transition: none;
        }

        @keyframes spinDice {
            0% {
                transform: rotateX(0deg) rotateY(0deg) rotateZ(0deg);
            }

            100% {
                transform: rotateX(360deg) rotateY(720deg) rotateZ(360deg);
            }
        }

        .dice-face {
            position: absolute;
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #f8fafc, #cbd5e1);
            border: 2px solid #94a3b8;
            border-radius: 20px;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.1), 0 0 10px rgba(0, 0, 0, 0.2);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            padding: 18px;
        }

        .dot {
            width: 22px;
            height: 22px;
            background: radial-gradient(circle at 30% 30%, #ef4444, #991b1b);
            border-radius: 50%;
            box-shadow: inset -2px -2px 4px rgba(0, 0, 0, 0.4), 2px 2px 4px rgba(255, 255, 255, 0.8);
            justify-self: center;
            align-self: center;
        }

        .face-1 {
            transform: rotateY(0deg) translateZ(60px);
        }

        .face-2 {
            transform: rotateY(180deg) translateZ(60px);
        }

        .face-3 {
            transform: rotateY(90deg) translateZ(60px);
        }

        .face-4 {
            transform: rotateY(-90deg) translateZ(60px);
        }

        .face-5 {
            transform: rotateX(90deg) translateZ(60px);
        }

        .face-6 {
            transform: rotateX(-90deg) translateZ(60px);
        }

        .show-1 {
            transform: rotateY(0deg);
        }

        .show-2 {
            transform: rotateY(-180deg);
        }

        .show-3 {
            transform: rotateY(-90deg);
        }

        .show-4 {
            transform: rotateY(90deg);
        }

        .show-5 {
            transform: rotateX(-90deg);
        }

        .show-6 {
            transform: rotateX(90deg);
        }

        [x-cloak] {
            display: none !important;
        }

        .nb-spin-ring {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #ec4899, #8b5cf6, #6366f1, #ec4899);
            animation: nb-spin 1s linear infinite;
            mask: radial-gradient(farthest-side, transparent calc(100% - 5px), #000 calc(100% - 4px));
            -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 5px), #000 calc(100% - 4px));
        }

        @keyframes nb-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .nb-btn-spinner {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border-width: 2px;
            border-style: solid;
            border-color: rgba(248, 250, 252, 0.6);
            border-top-color: transparent;
            animation: nb-spin 0.7s linear infinite;
        }

        .nb-pulse-dot {
            animation: nb-pulse-dot 1.4s ease-in-out infinite;
        }

        @keyframes nb-pulse-dot {

            0%,
            100% {
                opacity: .35;
                transform: scale(.92);
            }

            50% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .nb-toast-enter {
            animation: nb-toast-enter .25s ease-out;
        }

        @keyframes nb-toast-enter {
            from {
                opacity: 0;
                transform: translateY(8px) scale(.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .nb-card-shell {
            position: relative;
            aspect-ratio: 5 / 8;
            /* Mentok panjang Yu-Gi-Oh ratio */
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.42);
            transition: transform 0.2s ease, opacity 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
            background-size: 200% 200%;
            display: flex;
            flex-direction: column;
            contain: layout paint;
            content-visibility: auto;
            contain-intrinsic-size: 140px 224px;
        }

        .nb-card-shell.trap {
            background: linear-gradient(135deg, #7c2d12 0%, #450a0a 100%);
            border-color: #f87171;
        }

        .nb-card-shell.spell {
            background: linear-gradient(135deg, #064e3b 0%, #022c22 100%);
            border-color: #34d399;
        }

        .nb-card-shell.default {
            background: linear-gradient(180deg, #c6a76c 0%, #9a7b42 100%);
            border-color: #f1d095;
        }

        .nb-card-art {
            border: 2px solid rgba(0, 0, 0, 0.6);
            border-radius: 6px;
            overflow: hidden;
            background: #000;
            margin-bottom: 8px;
            height: 45%;
            /* Fixed art height to leave space for text */
            flex-shrink: 0;
        }

        .nb-card-art img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            opacity: 0.85;
            transition: 0.3s;
        }

        .nb-card-shell:hover .nb-card-art img {
            opacity: 1;
            transform: scale(1.05);
        }

        .nb-card-desc-box {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 6px;
            border-radius: 4px;
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 8px;
        }

        .nb-effect-burst {
            animation: nb-effect-arena-enter 0.42s cubic-bezier(.16, 1, .3, 1);
        }

        .nb-effect-arena {
            perspective: 1200px;
            transform-style: preserve-3d;
            overflow: visible;
        }

        .nb-effect-aura {
            position: absolute;
            inset: -18%;
            opacity: .28;
            pointer-events: none;
            background:
                conic-gradient(from 0deg, transparent, rgba(255, 255, 255, .18), transparent 34%, rgba(255, 255, 255, .1), transparent 68%),
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, .12), transparent 42%);
            animation: nb-effect-aura-spin 16s linear infinite;
        }

        .nb-effect-card-stage {
            position: relative;
            z-index: 30;
            min-height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            perspective: 1100px;
            isolation: isolate;
            overflow: visible;
        }

        .nb-effect-card-stage::before,
        .nb-effect-card-stage::after {
            content: '';
            position: absolute;
            border-radius: 9999px;
            pointer-events: none;
        }

        .nb-effect-card-stage::before {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, color-mix(in srgb, var(--nb-effect-primary) 26%, transparent), transparent 64%);
            opacity: .82;
            animation: nb-effect-orb-pulse 4.2s ease-in-out infinite;
        }

        .nb-effect-card-stage::after {
            width: 220px;
            height: 28px;
            bottom: 34px;
            background: rgba(0, 0, 0, .5);
            transform: rotateX(64deg);
            animation: nb-effect-shadow 3s ease-in-out infinite;
        }

        .nb-effect-card-stage.spell {
            --nb-effect-primary: #34d399;
            --nb-effect-secondary: #22d3ee;
            --nb-effect-deep: #064e3b;
            --nb-effect-glow: rgba(52, 211, 153, .68);
        }

        .nb-effect-card-stage.trap {
            --nb-effect-primary: #fb7185;
            --nb-effect-secondary: #f97316;
            --nb-effect-deep: #7f1d1d;
            --nb-effect-glow: rgba(248, 113, 113, .72);
        }

        .nb-effect-ring {
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 9999px;
            border: 1px solid color-mix(in srgb, var(--nb-effect-primary) 70%, transparent);
            box-shadow: inset 0 0 24px color-mix(in srgb, var(--nb-effect-primary) 18%, transparent);
            transform: rotateX(68deg) rotateZ(0deg);
            animation: nb-effect-ring-spin 9s linear infinite;
            opacity: .62;
            will-change: transform;
        }

        .nb-effect-ring:nth-child(2) {
            display: none;
        }

        .nb-effect-card-3d {
            position: relative;
            z-index: 60;
            width: min(230px, 68vw);
            aspect-ratio: 5 / 8;
            transform-style: preserve-3d;
            -webkit-transform-style: preserve-3d;
            animation: nb-effect-card-summon 1.65s cubic-bezier(.2, .85, .2, 1) both;
            will-change: transform, opacity;
        }

        .nb-effect-card-3d.is-floating {
            animation: nb-effect-card-float 4.2s ease-in-out infinite;
        }

        .nb-effect-card-face {
            position: absolute;
            inset: 0;
            overflow: hidden;
            border-radius: 18px;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            transform-style: preserve-3d;
            -webkit-transform-style: preserve-3d;
            box-shadow: 0 18px 32px rgba(0, 0, 0, .46), 0 0 16px color-mix(in srgb, var(--nb-effect-primary) 22%, transparent);
        }

        .nb-effect-card-back {
            display: flex;
            transform: rotateY(180deg);
            -webkit-transform: rotateY(180deg);
            align-items: center;
            justify-content: center;
            border: 4px solid #d6a457;
            background:
                repeating-conic-gradient(from 20deg, #4c1d95 0 8deg, #111827 8deg 18deg, #7c2d12 18deg 27deg),
                radial-gradient(circle at 50% 50%, #fbbf24, #7c2d12 34%, #020617 68%);
            box-shadow: inset 0 0 0 10px rgba(0, 0, 0, .35), inset 0 0 36px rgba(251, 191, 36, .26);
        }

        .nb-effect-card-back::before {
            content: '';
            width: 72%;
            aspect-ratio: 1;
            border-radius: 9999px;
            border: 2px solid rgba(255, 255, 255, .45);
            background: radial-gradient(circle, rgba(255, 255, 255, .28), transparent 58%);
            box-shadow: 0 0 30px rgba(251, 191, 36, .5);
        }

        .nb-effect-card-front {
            transform: translateZ(1px);
            -webkit-transform: translateZ(1px);
            border: 3px solid color-mix(in srgb, var(--nb-effect-primary) 86%, white 14%);
            background:
                radial-gradient(circle at 22% 16%, color-mix(in srgb, var(--nb-effect-secondary) 34%, transparent), transparent 34%),
                linear-gradient(155deg, color-mix(in srgb, var(--nb-effect-primary) 31%, #020617), #020617 42%, color-mix(in srgb, var(--nb-effect-deep) 85%, #000) 100%);
            box-shadow: inset 0 0 0 7px rgba(255, 255, 255, .08), inset 0 0 22px color-mix(in srgb, var(--nb-effect-primary) 18%, transparent);
        }

        .nb-effect-card-front.spell-card {
            border-radius: 18px 18px 22px 22px;
        }

        .nb-effect-card-front.trap-card {
            border-radius: 22px 22px 18px 18px;
        }

        .nb-effect-card-front::after {
            content: '';
            position: absolute;
            inset: -25% -70%;
            background: linear-gradient(115deg, transparent 43%, rgba(255, 255, 255, .72) 50%, transparent 57%);
            transform: translateX(-35%) rotate(12deg);
            animation: nb-effect-card-sheen 1.65s ease-out both;
            pointer-events: none;
        }

        .nb-effect-card-title {
            position: absolute;
            inset: 10px 10px auto;
            z-index: 2;
            border-radius: 10px;
            padding: 9px 10px;
            background: rgba(2, 6, 23, .82);
            border: 1px solid rgba(255, 255, 255, .18);
            font-size: .86rem;
            font-weight: 1000;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, .8);
        }

        .nb-effect-card-art-frame {
            position: absolute;
            inset: 64px 14px 94px;
            overflow: hidden;
            border-radius: 12px;
            border: 2px solid color-mix(in srgb, var(--nb-effect-primary) 54%, white 12%);
            background:
                radial-gradient(circle at 50% 38%, color-mix(in srgb, var(--nb-effect-primary) 22%, transparent), transparent 48%),
                #020617;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, .62), 0 0 14px color-mix(in srgb, var(--nb-effect-primary) 20%, transparent);
        }

        .nb-effect-card-art-frame .card-image,
        .nb-effect-card-art-frame img,
        .nb-effect-card-art-frame svg {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transform: translateZ(2px);
            -webkit-transform: translateZ(2px);
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        .nb-card-generated-art {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background:
                radial-gradient(circle at 30% 20%, rgba(255, 255, 255, .38), transparent 18%),
                radial-gradient(circle at 68% 68%, var(--nb-card-secondary, rgba(255, 255, 255, .18)), transparent 32%),
                linear-gradient(135deg, var(--nb-card-primary, #34d399), #020617 68%);
        }

        .nb-card-generated-art::before {
            content: '';
            position: absolute;
            inset: 10%;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, .26);
            box-shadow: 0 0 16px var(--nb-card-glow, rgba(52, 211, 153, .38)), inset 0 0 18px rgba(255, 255, 255, .07);
            transform: rotate(-18deg) scaleX(1.35);
        }

        .nb-card-generated-art::after {
            content: '';
            position: absolute;
            inset: -40% -25%;
            background: repeating-linear-gradient(115deg, transparent 0 14px, rgba(255, 255, 255, .08) 14px 16px);
            opacity: .45;
        }

        .nb-card-generated-art-icon {
            position: relative;
            z-index: 1;
            font-size: inherit;
            text-shadow: 0 8px 14px rgba(0, 0, 0, .6);
            transform: translateZ(22px);
        }

        .nb-card-effect-art {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        .nb-card-effect-art-icon {
            position: relative;
            z-index: 2;
            font-size: 5.2rem;
            line-height: 1;
            text-shadow: 0 8px 16px rgba(0, 0, 0, .6);
        }

        .nb-card-effect-art-name {
            position: relative;
            z-index: 2;
            max-width: 88%;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, .22);
            background: rgba(2, 6, 23, .58);
            padding: 6px 10px;
            color: rgba(255, 255, 255, .9);
            font-size: .7rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .nb-effect-card-desc {
            position: absolute;
            inset: auto 14px 16px;
            max-height: 64px;
            overflow: hidden;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, .14);
            background: rgba(2, 6, 23, .78);
            padding: 9px;
            color: rgba(255, 255, 255, .82);
            font-size: .7rem;
            line-height: 1.25;
        }

        .nb-effect-sigil {
            position: absolute;
            width: 14px;
            height: 14px;
            border-radius: 9999px;
            background: var(--nb-effect-primary);
            box-shadow: 0 0 10px 3px var(--nb-effect-glow);
            opacity: 0;
            animation: nb-effect-sigil 1.55s ease-out .28s both;
        }

        .nb-effect-sigil:nth-of-type(1) { top: 14%; left: 15%; }
        .nb-effect-sigil:nth-of-type(2) { top: 20%; right: 12%; animation-delay: .38s; }
        .nb-effect-sigil:nth-of-type(3) { bottom: 22%; left: 11%; animation-delay: .5s; }
        .nb-effect-sigil:nth-of-type(4) { bottom: 17%; right: 18%; animation-delay: .62s; }

        .nb-effect-info-panel {
            position: relative;
            z-index: 20;
            animation: nb-effect-info-rise .52s cubic-bezier(.16, 1, .3, 1) .38s both;
        }

        .loadout-overlay {
            background: rgba(15, 23, 42, .97);
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
        }

        .loadout-panel,
        .loadout-card-grid,
        .loadout-preview {
            contain: layout paint;
        }

        .loadout-card-grid {
            content-visibility: auto;
            contain-intrinsic-size: 780px;
        }

        .loadout-card {
            transform: translateZ(0);
            will-change: transform;
        }

        .loadout-card:hover {
            box-shadow: 0 14px 24px rgba(0, 0, 0, .42);
        }

        @supports (-webkit-hyphens: none) {
            .loadout-overlay,
            .modal-backdrop-lite {
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }
        }

        .nb-target-roulette-panel {
            overflow: hidden;
            animation: nb-effect-arena-enter .28s cubic-bezier(.16, 1, .3, 1);
        }

        .nb-target-gacha {
            position: relative;
            min-height: 390px;
            overflow: hidden;
            border-radius: 30px;
            border: 1px solid rgba(250, 204, 21, .34);
            background:
                radial-gradient(circle at 50% 40%, rgba(250, 204, 21, .18), transparent 34%),
                radial-gradient(circle at 50% 100%, rgba(220, 38, 38, .18), transparent 42%),
                linear-gradient(180deg, rgba(15, 23, 42, .96), rgba(69, 10, 10, .72));
            box-shadow: inset 0 0 54px rgba(0, 0, 0, .62), 0 24px 56px rgba(0, 0, 0, .42);
        }

        .nb-target-gacha::before {
            content: '';
            position: absolute;
            inset: -45%;
            background: conic-gradient(from 0deg, transparent, rgba(250, 204, 21, .16), transparent, rgba(239, 68, 68, .12), transparent);
            animation: nb-target-gacha-aura 1.35s linear infinite;
        }

        .nb-target-gacha-wheel-wrap {
            position: absolute;
            left: 50%;
            top: 52%;
            width: min(350px, 78vw);
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
        }

        .nb-target-gacha-pointer {
            position: absolute;
            left: 50%;
            top: -10px;
            z-index: 8;
            width: 0;
            height: 0;
            border-left: 22px solid transparent;
            border-right: 22px solid transparent;
            border-top: 52px solid #facc15;
            filter: drop-shadow(0 8px 14px rgba(0, 0, 0, .5)) drop-shadow(0 0 18px rgba(250, 204, 21, .45));
            transform: translateX(-50%);
            animation: nb-target-pointer-tick .18s ease-in-out 14 alternate;
        }

        .nb-target-gacha-pointer::after {
            content: '';
            position: absolute;
            left: -9px;
            top: -45px;
            width: 18px;
            height: 18px;
            border-radius: 9999px;
            background: #7f1d1d;
            box-shadow: inset 0 0 0 3px rgba(255, 255, 255, .38);
        }

        .nb-target-gacha-wheel {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            border: 5px solid rgba(250, 204, 21, .78);
            background:
                radial-gradient(circle, rgba(15, 23, 42, .96) 0 23%, transparent 24%),
                repeating-conic-gradient(from -18deg, rgba(250, 204, 21, .9) 0 30deg, rgba(185, 28, 28, .88) 30deg 60deg, rgba(30, 41, 59, .92) 60deg 90deg);
            box-shadow: inset 0 0 34px rgba(0, 0, 0, .58), 0 0 46px rgba(250, 204, 21, .22);
            animation: nb-target-gacha-spin 2.65s cubic-bezier(.12, .72, .1, 1) forwards;
        }

        .nb-target-gacha-wheel::before {
            content: '';
            position: absolute;
            inset: 14px;
            border-radius: inherit;
            border: 1px dashed rgba(255, 255, 255, .48);
        }

        .nb-target-gacha-wheel::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 86px;
            aspect-ratio: 1;
            border-radius: 9999px;
            border: 3px solid rgba(250, 204, 21, .78);
            background: radial-gradient(circle at 35% 28%, rgba(255, 255, 255, .34), rgba(127, 29, 29, .96) 58%);
            box-shadow: 0 0 28px rgba(0, 0, 0, .45);
            transform: translate(-50%, -50%);
        }

        .nb-target-gacha-segment {
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 3;
            width: 118px;
            margin-left: -59px;
            transform: rotate(var(--angle)) translateY(-122px) rotate(calc(-1 * var(--angle)));
            transform-origin: 50% 50%;
        }

        .nb-target-gacha-name {
            display: inline-flex;
            width: 118px;
            height: 34px;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, .45);
            background: rgba(255, 251, 235, .94);
            color: #431407;
            font-size: .72rem;
            font-weight: 1000;
            letter-spacing: .04em;
            text-transform: uppercase;
            box-shadow: 0 8px 16px rgba(0, 0, 0, .28);
        }

        .nb-target-gacha-winner {
            position: absolute;
            left: 50%;
            bottom: 20px;
            z-index: 10;
            display: flex;
            max-width: min(440px, 86vw);
            align-items: center;
            gap: 12px;
            padding: 13px 18px;
            border-radius: 20px;
            border: 2px solid rgba(250, 204, 21, .82);
            background: linear-gradient(135deg, rgba(255, 251, 235, .98), rgba(253, 230, 138, .96));
            color: #431407;
            box-shadow: 0 24px 54px rgba(0, 0, 0, .46), 0 0 44px rgba(250, 204, 21, .32);
            opacity: 0;
            transform: translateX(-50%) translateY(24px) scale(.88);
            animation: nb-target-gacha-winner .62s cubic-bezier(.16, 1, .3, 1) 2.45s both;
        }

        .nb-target-gacha-initial {
            display: inline-flex;
            width: 48px;
            height: 48px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: linear-gradient(135deg, #f59e0b, #dc2626);
            color: white;
            font-size: 1.3rem;
            font-weight: 1000;
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .34);
        }

        .nb-target-gacha-name-winner {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: clamp(1.2rem, 4.5vw, 2rem);
            font-weight: 1000;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .nb-target-gacha-burst {
            position: absolute;
            inset: 0;
            z-index: 7;
            opacity: 0;
            background: radial-gradient(circle at 50% 50%, rgba(250, 204, 21, .42), transparent 36%);
            animation: nb-target-gacha-burst .72s ease-out 2.38s both;
            pointer-events: none;
        }

        @keyframes nb-target-gacha-spin {
            0% { transform: rotate(0deg); filter: blur(0); }
            30% { filter: blur(1.6px); }
            72% { filter: blur(.65px); }
            88% { transform: rotate(calc(var(--target-rotation) + 18deg)); filter: blur(0); }
            100% { transform: rotate(var(--target-rotation)); filter: blur(0); }
        }

        @keyframes nb-target-gacha-aura {
            to { transform: rotate(360deg); }
        }

        @keyframes nb-target-pointer-tick {
            from { transform: translateX(-50%) rotate(-5deg); }
            to { transform: translateX(-50%) rotate(5deg); }
        }

        @keyframes nb-target-gacha-winner {
            to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
        }

        @keyframes nb-target-gacha-burst {
            0% { opacity: 0; transform: scale(.55); }
            48% { opacity: 1; transform: scale(1.06); }
            100% { opacity: 0; transform: scale(1.46); }
        }

        @keyframes nb-effect-arena-enter {
            from { opacity: 0; transform: translateY(14px) scale(.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes nb-effect-aura-spin {
            to { transform: rotate(360deg); }
        }

        @keyframes nb-effect-orb-pulse {
            0%, 100% { transform: scale(.85); opacity: .48; }
            50% { transform: scale(1.12); opacity: .9; }
        }

        @keyframes nb-effect-shadow {
            0%, 100% { transform: rotateX(64deg) scale(.9); opacity: .48; }
            50% { transform: rotateX(64deg) scale(1.14); opacity: .78; }
        }

        @keyframes nb-effect-ring-spin {
            to { transform: rotateX(68deg) rotateZ(360deg); }
        }

        @keyframes nb-effect-card-summon {
            0% { opacity: 0; transform: translate3d(0, 58px, -120px) rotateX(54deg) rotateY(200deg) rotateZ(-8deg) scale(.72); }
            22% { opacity: 1; transform: translate3d(0, -14px, 30px) rotateX(38deg) rotateY(240deg) rotateZ(6deg) scale(.92); }
            58% { transform: translate3d(0, -4px, 76px) rotateX(10deg) rotateY(326deg) rotateZ(-2deg) scale(1.04); }
            76% { transform: translate3d(0, 0, 44px) rotateX(3deg) rotateY(350deg) rotateZ(1deg) scale(1.03); }
            100% { opacity: 1; transform: translate3d(0, 0, 0) rotateX(0deg) rotateY(360deg) rotateZ(0deg) scale(1); }
        }

        @keyframes nb-effect-card-float {
            0%, 100% { transform: translateY(0) rotateX(0deg) rotateY(360deg) rotateZ(0deg); }
            50% { transform: translateY(-7px) rotateX(2deg) rotateY(362deg) rotateZ(-.5deg); }
        }

        @keyframes nb-effect-card-sheen {
            0%, 48% { opacity: 0; transform: translateX(-38%) rotate(12deg); }
            64% { opacity: .82; }
            100% { opacity: 0; transform: translateX(72%) rotate(12deg); }
        }

        @keyframes nb-effect-sigil {
            0% { opacity: 0; transform: translate3d(0, 18px, 0) scale(.5); }
            28% { opacity: 1; }
            100% { opacity: 0; transform: translate3d(0, -72px, 0) scale(1.35); }
        }

        @keyframes nb-effect-info-rise {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .nb-effect-burst,
            .nb-effect-aura,
            .nb-effect-card-3d,
            .nb-effect-ring,
            .nb-effect-sigil,
            .nb-effect-info-panel,
            .nb-effect-card-front::after,
            .nb-target-roulette-panel,
            .nb-target-gacha::before,
            .nb-target-gacha-wheel,
            .nb-target-gacha-pointer,
            .nb-target-gacha-winner,
            .nb-target-gacha-burst {
                animation: none !important;
            }
        }

        .nb-roll-result-burst {
            animation: nb-roll-result-burst 1.15s ease-out forwards;
        }

        @keyframes nb-roll-result-burst {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(.65);
                filter: blur(6px);
            }

            18% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.24);
                filter: blur(0);
            }

            45% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }

            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(1.12);
            }
        }

        .nb-gameover-card {
            animation: nb-gameover-card 0.75s cubic-bezier(.16, 1, .3, 1);
        }

        @keyframes nb-gameover-card {
            from {
                opacity: 0;
                transform: translateY(26px) scale(.92);
                filter: blur(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0);
            }
        }

        .card-image,
        .card-image svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .nb-card-icon-art {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 50% 38%, rgba(255, 255, 255, 0.18), transparent 42%),
                linear-gradient(135deg, rgba(15, 23, 42, 0.35), rgba(0, 0, 0, 0.9));
            text-shadow: 0 8px 18px rgba(0, 0, 0, 0.55);
            line-height: 1;
        }

        .nb-card-icon-art.spell {
            color: #d1fae5;
        }

        .nb-card-icon-art.trap {
            color: #fee2e2;
        }

        .nb-card-icon-art-sm {
            font-size: 2.15rem;
        }

        .nb-card-icon-art-md {
            font-size: 4.5rem;
        }

        .nb-card-icon-art-lg {
            font-size: 5.25rem;
        }


        .arena-shell {
            min-height: 100vh;
            background:
                radial-gradient(circle at 50% 12%, rgba(20, 184, 166, 0.22), transparent 32%),
                radial-gradient(circle at 10% 85%, rgba(239, 68, 68, 0.18), transparent 28%),
                radial-gradient(circle at 90% 75%, rgba(245, 158, 11, 0.16), transparent 28%),
                linear-gradient(145deg, #05070b 0%, #0d1420 42%, #020617 100%);
        }

        .arena-shell::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .24;
            background-image:
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(circle at center, #000, transparent 78%);
        }

        .arena-wrap {
            perspective: 1400px;
        }

        .battle-board {
            position: relative;
            min-height: 660px;
            margin-bottom: 56px;
            border-radius: 38px;
            overflow: visible;
            background:
                radial-gradient(ellipse at center, rgba(251, 191, 36, .16), transparent 38%),
                linear-gradient(90deg, rgba(8, 13, 20, .94), rgba(23, 18, 13, .9) 17%, rgba(92, 62, 34, .92) 50%, rgba(23, 18, 13, .9) 83%, rgba(8, 13, 20, .94)),
                repeating-linear-gradient(90deg, #5c3920 0 90px, #684326 90px 180px);
            border: 1px solid rgba(125, 211, 252, .25);
            box-shadow: 0 35px 100px rgba(0, 0, 0, .62), inset 0 0 0 2px rgba(255, 255, 255, .04);
            transform: rotateX(7deg);
            transform-origin: center top;
        }

        .battle-board::before {
            content: "";
            position: absolute;
            inset: 34px;
            border-radius: 30px;
            border: 2px solid rgba(34, 211, 238, .78);
            box-shadow: 0 0 22px rgba(34, 211, 238, .7), inset 0 0 36px rgba(34, 211, 238, .18);
            pointer-events: none;
        }

        .battle-board::after {
            content: "";
            position: absolute;
            left: 7%;
            right: 7%;
            top: 50%;
            border-top: 2px solid rgba(15, 23, 42, .65);
            box-shadow: 0 0 0 1px rgba(255,255,255,.06), 0 14px 36px rgba(0,0,0,.45);
            pointer-events: none;
        }

        .arena-corner {
            position: absolute;
            width: 150px;
            height: 150px;
            border-radius: 30px;
            background: linear-gradient(145deg, rgba(15,23,42,.95), rgba(2,6,23,.72));
            border: 1px solid rgba(255,255,255,.12);
            box-shadow: inset 0 0 28px rgba(0,0,0,.7), 0 20px 40px rgba(0,0,0,.34);
        }

        .arena-card-avatar {
            transform-style: preserve-3d;
            transform: rotateX(-9deg) translateZ(24px);
            transition: transform .24s ease, filter .24s ease;
            min-height: 112px;
        }

        .arena-card-avatar.active {
            filter: drop-shadow(0 0 20px rgba(34, 211, 238, .8));
            transform: rotateX(-9deg) translateZ(36px) scale(1.04);
        }

        .score-delta-slot {
            min-height: 1rem;
        }

        .player-token {
            background: linear-gradient(160deg, rgba(15, 23, 42, .98), rgba(30, 41, 59, .86));
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 18px 36px rgba(0,0,0,.42), inset 0 1px 0 rgba(255,255,255,.08);
        }

        .hand-zone {
            position: relative;
            z-index: 40;
            background: linear-gradient(180deg, rgba(2, 6, 23, .2), rgba(2, 6, 23, .88));
            border-top: 1px solid rgba(34, 211, 238, .22);
            box-shadow: 0 -22px 50px rgba(2, 6, 23, .78);
        }

        .hand-card {
            position: relative;
            z-index: 41;
            width: 110px;
            height: 168px;
            flex: 0 0 108px;
            transform: translateY(0) rotate(var(--tilt, 0deg));
            transition: transform .2s ease, filter .2s ease;
        }

        .hand-card:hover {
            transform: translateY(-10px) rotate(0deg) scale(1.04);
            z-index: 55;
            filter: drop-shadow(0 16px 24px rgba(34, 211, 238, .22));
        }

        .command-orb {
            box-shadow: 0 0 26px rgba(34, 211, 238, .45), inset 0 0 30px rgba(251, 146, 60, .18);
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        @media (max-width: 900px) {
            .battle-board {
                min-height: 780px;
                transform: none;
            }

            .battle-board::before {
                inset: 18px;
            }
        }
    </style>
