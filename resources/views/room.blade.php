<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room {{ $room->code }} - Sang Terpilih</title>
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
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-size: 200% 200%;
            display: flex;
            flex-direction: column;
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
            inset: -30%;
            opacity: .42;
            pointer-events: none;
            background:
                conic-gradient(from 0deg, transparent, rgba(255, 255, 255, .18), transparent 34%, rgba(255, 255, 255, .1), transparent 68%),
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, .12), transparent 42%);
            animation: nb-effect-aura-spin 9s linear infinite;
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
            width: 290px;
            height: 290px;
            background: radial-gradient(circle, color-mix(in srgb, var(--nb-effect-primary) 26%, transparent), transparent 64%);
            opacity: .82;
            animation: nb-effect-orb-pulse 2.8s ease-in-out infinite;
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
            width: 318px;
            height: 318px;
            border-radius: 9999px;
            border: 1px solid color-mix(in srgb, var(--nb-effect-primary) 70%, transparent);
            box-shadow: inset 0 0 24px color-mix(in srgb, var(--nb-effect-primary) 18%, transparent);
            transform: rotateX(68deg) rotateZ(0deg);
            animation: nb-effect-ring-spin 5.2s linear infinite;
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
            animation: nb-effect-card-summon 1.65s cubic-bezier(.2, .85, .2, 1) both, nb-effect-card-float 4.2s ease-in-out 1.65s infinite;
            will-change: transform, opacity;
        }

        .nb-effect-card-face {
            position: absolute;
            inset: 0;
            overflow: hidden;
            border-radius: 18px;
            backface-visibility: hidden;
            transform-style: preserve-3d;
            box-shadow: 0 24px 42px rgba(0, 0, 0, .52), 0 0 22px color-mix(in srgb, var(--nb-effect-primary) 28%, transparent);
        }

        .nb-effect-card-back {
            display: flex;
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
            transform: rotateY(180deg);
            border: 3px solid color-mix(in srgb, var(--nb-effect-primary) 86%, white 14%);
            background:
                radial-gradient(circle at 22% 16%, color-mix(in srgb, var(--nb-effect-secondary) 34%, transparent), transparent 34%),
                linear-gradient(155deg, color-mix(in srgb, var(--nb-effect-primary) 31%, #020617), #020617 42%, color-mix(in srgb, var(--nb-effect-deep) 85%, #000) 100%);
            box-shadow: inset 0 0 0 7px rgba(255, 255, 255, .08), inset 0 0 34px color-mix(in srgb, var(--nb-effect-primary) 22%, transparent);
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
            box-shadow: inset 0 0 28px rgba(0, 0, 0, .68), 0 0 24px color-mix(in srgb, var(--nb-effect-primary) 26%, transparent);
        }

        .nb-effect-card-art-frame .card-image,
        .nb-effect-card-art-frame img,
        .nb-effect-card-art-frame svg {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
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
            box-shadow: 0 0 28px var(--nb-card-glow, rgba(52, 211, 153, .5)), inset 0 0 30px rgba(255, 255, 255, .08);
            transform: rotate(-18deg) scaleX(1.35);
        }

        .nb-card-generated-art::after {
            content: '';
            position: absolute;
            inset: -40% -25%;
            background: repeating-linear-gradient(115deg, transparent 0 14px, rgba(255, 255, 255, .08) 14px 16px);
            mix-blend-mode: screen;
            opacity: .7;
        }

        .nb-card-generated-art-icon {
            position: relative;
            z-index: 1;
            font-size: inherit;
            text-shadow: 0 10px 18px rgba(0, 0, 0, .65), 0 0 18px var(--nb-card-glow, rgba(255, 255, 255, .45));
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
            text-shadow: 0 10px 20px rgba(0, 0, 0, .62), 0 0 22px var(--nb-card-glow, rgba(255, 255, 255, .45));
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
            box-shadow: 0 0 16px 5px var(--nb-effect-glow);
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
            0% { opacity: 0; transform: translate3d(0, 58px, -120px) rotateX(54deg) rotateY(-160deg) rotateZ(-8deg) scale(.72); }
            22% { opacity: 1; transform: translate3d(0, -14px, 30px) rotateX(38deg) rotateY(-120deg) rotateZ(6deg) scale(.92); }
            58% { transform: translate3d(0, -4px, 76px) rotateX(10deg) rotateY(34deg) rotateZ(-2deg) scale(1.04); }
            76% { transform: translate3d(0, 0, 44px) rotateX(3deg) rotateY(190deg) rotateZ(1deg) scale(1.03); }
            100% { opacity: 1; transform: translate3d(0, 0, 0) rotateX(0deg) rotateY(180deg) rotateZ(0deg) scale(1); }
        }

        @keyframes nb-effect-card-float {
            0%, 100% { transform: translateY(0) rotateX(0deg) rotateY(180deg) rotateZ(0deg); }
            50% { transform: translateY(-7px) rotateX(2deg) rotateY(182deg) rotateZ(-.5deg); }
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
    </style>
</head>

<body
    class="min-h-screen flex flex-col p-6 items-center relative overflow-x-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-black">

    <div x-data="gameClient()" x-init="initEcho()" class="w-full max-w-5xl z-10">

        <!-- Global loading: start game / leave room (DB + redirect) -->
        <div x-show="loadingStart || loadingLeave" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-md">
            <div
                class="glass-panel rounded-3xl px-10 py-12 max-w-sm w-full text-center border border-white/10 shadow-[0_0_60px_rgba(139,92,246,0.2)]">
                <div class="nb-spin-ring mx-auto mb-6"></div>
                <p class="text-lg font-semibold text-white tracking-tight"
                    x-text="loadingLeave ? 'Meninggalkan room…' : 'Memulai permainan…'"></p>
                <p class="text-sm text-slate-400 mt-2">Menyimpan ke server</p>
                <div class="flex justify-center gap-1.5 mt-6">
                    <span class="w-2 h-2 rounded-full bg-pink-500 nb-pulse-dot" style="animation-delay: 0ms"></span>
                    <span class="w-2 h-2 rounded-full bg-violet-500 nb-pulse-dot" style="animation-delay: 150ms"></span>
                    <span class="w-2 h-2 rounded-full bg-emerald-400 nb-pulse-dot"
                        style="animation-delay: 300ms"></span>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div x-show="toast.show" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2" class="fixed bottom-6 right-6 z-[120] max-w-sm">
            <div class="nb-toast-enter px-4 py-3 rounded-xl border shadow-2xl backdrop-blur-sm"
                :class="toast.type === 'error' ? 'bg-red-900/80 border-red-400/50 text-red-100' : 'bg-emerald-900/80 border-emerald-400/50 text-emerald-100'">
                <p class="text-sm font-semibold" x-text="toast.message"></p>
            </div>
        </div>

        <!-- Random Target Roulette -->
        <div x-show="targetRoulette.show" x-cloak
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-950/78 backdrop-blur-md overflow-hidden">
            <div class="nb-target-roulette-panel w-full max-w-3xl rounded-[2rem] border border-yellow-300/40 bg-gradient-to-br from-amber-950/90 via-slate-950/95 to-red-950/90 p-5 md:p-7 text-center shadow-[0_0_80px_rgba(250,204,21,0.18)]">
                <p class="text-xs uppercase tracking-[0.35em] font-black text-yellow-200/80">Random Target Lock</p>
                <h3 class="mt-2 text-3xl md:text-5xl font-black text-white">Menentukan Korban...</h3>
                <p class="mt-2 text-sm text-slate-300">
                    <span x-text="targetRoulette.cardName"></span>
                    <span class="text-yellow-200 font-bold"> memilih target secara acak</span>
                </p>

                <div class="nb-target-gacha mt-5" :key="targetRoulette.animationKey">
                    <div class="nb-target-gacha-wheel-wrap">
                        <div class="nb-target-gacha-pointer"></div>
                        <div class="nb-target-gacha-wheel" :style="`--target-rotation: ${targetRoulette.wheelRotation}deg`">
                            <template x-for="(player, index) in targetRoulette.wheelPlayers" :key="`${targetRoulette.animationKey}-${index}-${player.id}`">
                                <div class="nb-target-gacha-segment" :style="`--angle: ${targetRoulette.segmentAngles[index]}deg`">
                                    <span class="nb-target-gacha-name truncate" x-text="player.name || '-'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="nb-target-gacha-burst"></div>
                    <div class="nb-target-gacha-winner">
                        <span class="nb-target-gacha-initial" x-text="targetInitial(targetRoulette.selectedPlayer)"></span>
                        <span class="nb-target-gacha-name-winner" x-text="targetRoulette.selectedPlayer?.name || '-'"></span>
                    </div>
                </div>

                <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-yellow-300/25 bg-yellow-300/10 px-4 py-2 text-xs font-black uppercase tracking-[0.22em] text-yellow-100">
                    <span class="h-2 w-2 rounded-full bg-yellow-300 animate-pulse"></span>
                    <span x-text="targetRoulette.locked ? 'Jarum berhenti ke target terpilih' : 'Jarum gacha sedang berputar'"></span>
                </div>
            </div>
        </div>

        <!-- Card Effect Announcement -->
        <div x-show="effectNotice.show" x-cloak @click.self="closeEffectNotice()"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[155] flex items-center justify-center p-3 md:p-4 bg-slate-950/75 backdrop-blur-md overflow-y-auto">
            <div class="nb-effect-burst nb-effect-arena max-w-5xl w-full rounded-[2rem] border p-4 md:p-6 shadow-2xl backdrop-blur-md overflow-visible relative my-8"
                :class="effectNotice.type === 'trap'
                    ? 'bg-gradient-to-br from-red-950/95 via-slate-950/95 to-red-900/80 border-red-400/60 text-red-50 shadow-red-900/30'
                    : 'bg-gradient-to-br from-emerald-950/95 via-slate-950/95 to-teal-900/80 border-emerald-400/60 text-emerald-50 shadow-emerald-900/30'">
                <div class="nb-effect-aura"
                    :class="effectNotice.type === 'trap' ? 'mix-blend-screen' : 'mix-blend-screen'"></div>
                <div class="absolute inset-x-0 top-0 h-1"
                    :class="effectNotice.type === 'trap' ? 'bg-red-400' : 'bg-emerald-400'"></div>

                <button type="button" @click="closeEffectNotice()"
                    class="absolute right-4 top-4 z-20 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-bold text-white/80 hover:bg-white/20 hover:text-white transition">
                    Tutup
                </button>

                <div class="relative z-10 grid grid-cols-1 lg:grid-cols-[minmax(300px,0.9fr)_minmax(0,1.1fr)] gap-5 md:gap-7 items-center">
                    <div class="nb-effect-card-stage" :class="effectNotice.type" :style="effectNotice.cardStyle">
                        <div class="nb-effect-ring"></div>
                        <span class="nb-effect-sigil"></span>
                        <span class="nb-effect-sigil"></span>
                        <span class="nb-effect-sigil"></span>

                        <div class="nb-effect-card-3d" :key="effectNotice.animationKey">
                            <div class="nb-effect-card-face nb-effect-card-back"></div>
                            <div class="nb-effect-card-face nb-effect-card-front" :class="effectNotice.type === 'trap' ? 'trap-card' : 'spell-card'">
                                <div class="nb-effect-card-title" x-text="effectNotice.cardName"></div>
                                <div class="nb-effect-card-art-frame">
                                    <span class="card-image" x-html="effectNotice.cardArt"></span>
                                </div>
                                <div class="nb-effect-card-desc" x-text="effectNotice.cardDescription"></div>
                            </div>
                        </div>
                    </div>

                    <div class="nb-effect-info-panel min-w-0">
                        <div class="inline-flex items-center gap-3 rounded-full border px-4 py-2 text-xs font-black uppercase tracking-[0.24em] shadow-lg"
                            :class="effectNotice.type === 'trap'
                                ? 'border-red-300/40 bg-red-500/15 text-red-100 shadow-red-900/20'
                                : 'border-emerald-300/40 bg-emerald-500/15 text-emerald-100 shadow-emerald-900/20'">
                            <span class="text-lg" x-text="effectNotice.icon"></span>
                            <span x-text="effectNotice.type === 'trap' ? 'Trap Activated' : 'Spell Activated'"></span>
                        </div>

                        <h4 class="mt-4 text-4xl md:text-6xl font-black leading-[0.92] tracking-tight text-white drop-shadow-2xl" x-text="effectNotice.cardName"></h4>
                        <p class="mt-4 text-sm md:text-base opacity-80 leading-relaxed" x-text="effectNotice.cardDescription"></p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-6">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-inner">
                                <p class="text-[10px] uppercase tracking-[0.2em] opacity-60 mb-1">Diaktifkan oleh</p>
                                <p class="text-lg md:text-xl font-black truncate" x-text="effectNotice.usedByName"></p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-inner">
                                <p class="text-[10px] uppercase tracking-[0.2em] opacity-60 mb-1">Target efek</p>
                                <p class="text-lg md:text-xl font-black truncate" x-text="effectNotice.targetName"></p>
                                <p x-show="effectNotice.isRandom" class="text-xs mt-1 text-yellow-200 font-bold">Target dipilih acak</p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-white/10 bg-black/30 p-4 md:p-5 shadow-inner">
                            <p class="text-[10px] uppercase tracking-[0.2em] opacity-60 mb-2">Detail efek</p>
                            <p class="text-base md:text-xl font-bold leading-relaxed" x-text="effectNotice.message"></p>
                        </div>

                        <p class="mt-4 text-xs uppercase tracking-[0.22em] text-white/45 font-bold">
                            3D Card Summon Sequence
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Over Reveal -->
        <div x-show="gameOverSequence.spotlight" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[158] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
            <div class="nb-gameover-card w-full max-w-3xl rounded-3xl border border-yellow-300/40 bg-gradient-to-br from-slate-950 via-violet-950 to-slate-900 p-6 md:p-8 text-center shadow-[0_0_80px_rgba(250,204,21,0.25)]">
                <p class="text-xs uppercase tracking-[0.35em] text-yellow-200 font-black mb-3">Survival Result</p>
                <h2 class="text-4xl md:text-6xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 via-amber-400 to-pink-400 mb-7">
                    Duel Selesai!
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-yellow-300/40 bg-yellow-300/10 p-5">
                        <p class="text-xs uppercase tracking-[0.2em] text-yellow-200 mb-2">Pemenang</p>
                        <div class="text-5xl mb-2">👑</div>
                        <p class="text-3xl font-black text-yellow-200 truncate" x-text="gameOverSequence.winner?.name || '-'"></p>
                        <p class="text-sm text-yellow-100/80 mt-1">LP akhir: <span class="font-mono font-bold" x-text="formatScore(gameOverSequence.winner?.score || 0)"></span></p>
                    </div>
                    <div class="rounded-2xl border border-red-300/40 bg-red-500/10 p-5">
                        <p class="text-xs uppercase tracking-[0.2em] text-red-200 mb-2">Terbawah</p>
                        <div class="text-5xl mb-2">💥</div>
                        <p class="text-3xl font-black text-red-100 truncate" x-text="gameOverSequence.loser?.name || '-'"></p>
                        <p class="text-sm text-red-100/80 mt-1">LP akhir: <span class="font-mono font-bold" x-text="formatScore(gameOverSequence.loser?.score || 0)"></span></p>
                    </div>
                </div>
                <p class="text-slate-300 mt-6 text-sm">Leaderboard lengkap segera ditampilkan...</p>
            </div>
        </div>

        <!-- Header -->
        <div class="flex justify-between items-center mb-10 w-full glass-panel px-6 py-4 rounded-xl">
            <div>
                <h1
                    class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500">
                    Sang Terpilih @hasSection('mode_name')<span class="text-xl ml-2 text-pink-300">-
                    @yield('mode_name')</span>@endif</h1>
                <button @click="showHistoryModal = true"
                    class="mt-2 text-xs bg-slate-800/70 border border-violet-400/30 hover:border-violet-300 hover:bg-slate-700/70 px-3 py-1.5 rounded-full text-violet-200 transition">
                    Action History
                </button>
                <p class="text-slate-400">Room: <span class="text-white font-bold">{{ $room->code }}</span></p>
                <p class="text-xs text-slate-500 mt-1" x-show="status === 'playing'">Ronde <span
                        class="text-white font-bold" x-text="currentRound"></span> / <span x-text="totalRounds"></span>
                </p>
            </div>
            <div class="text-right flex items-center space-x-4">
                <p class="text-slate-400">You are <span
                        class="font-bold text-violet-400 truncate max-w-[180px] inline-block align-bottom" title="{{ $currentPlayer->name }}">{{ Str::limit($currentPlayer->name, 25, '') }}</span></p>

                <button x-show="status === 'waiting'" @click="leaveRoom" :disabled="loadingLeave || loadingStart"
                    class="text-red-400 hover:text-white transition text-xs font-bold border border-red-500/30 px-3 py-1.5 rounded hover:bg-red-500/80 shadow-md disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-1.5">
                    <span x-show="loadingLeave" class="nb-btn-spinner"></span>
                    <span x-text="loadingLeave ? 'Keluar…' : 'Keluar Room'"></span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Sidebar: Player List -->
            <div class="glass-panel rounded-2xl p-6 h-full flex flex-col">
                <h2 class="text-xl font-bold mb-4 text-pink-400 border-b border-white/10 pb-2">Players</h2>
                <ul class="space-y-3 flex-1 overflow-y-auto">
                    <template x-for="p in players" :key="p.id">
                        <li class="flex justify-between items-center bg-slate-800/50 px-4 py-3 rounded-lg border border-slate-700/50"
                            :class="{'ring-2 ring-violet-500 bg-violet-900/30': status === 'playing' && currentTurn === p.id}">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2 relative min-w-0">
                                    <span class="w-3 h-3 rounded-full shrink-0"
                                        :class="p.id === currentTurn && status === 'playing' ? 'bg-green-400 animate-pulse' : 'bg-slate-600'"></span>

                                    <!-- Trap Countdown Icon -->
                                    <template x-if="getTrapTurns(p) > 0">
                                        <div class="flex items-center justify-center w-5 h-5 bg-red-600 text-white rounded-full text-[10px] font-bold border border-red-400 shadow-[0_0_8px_rgba(220,38,38,0.5)] animate-bounce shrink-0" title="Trap is going to affect this player!">
                                            <span x-text="getTrapTurns(p)"></span>
                                        </div>
                                    </template>

                                    <span x-text="p.name.slice(0, 25)" class="truncate inline-block align-bottom" :title="p.name"
                                        :class="{'font-bold text-violet-300': p.id == currentPlayerId}"></span>
                                    <span x-show="p.is_host"
                                        class="text-xs bg-pink-500/20 text-pink-300 px-2 py-0.5 rounded ml-1 shrink-0">Host</span>
                                </div>
                                <div x-show="p.active_buffs && p.active_buffs.length > 0" class="flex flex-wrap gap-1 ml-5">
                                    <template x-for="buff in p.active_buffs">
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-700/50 text-slate-300 border border-slate-500/30 font-mono" x-text="buff.split('_').join(' ').split(':').join(' ').toUpperCase()"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-slate-400 font-normal uppercase tracking-wider mb-0.5">
                                    @yield('score_label', 'Score')</div>
                                <div class="font-bold leading-none font-mono tabular-nums transition-colors"
                                    :class="p.scoreDelta < 0 ? 'text-red-300' : (p.scoreDelta > 0 ? 'text-emerald-300' : 'text-amber-400')"
                                    x-text="status !== 'waiting' ? formatScore(p.displayScore ?? p.score) : '-'"></div>
                                <div x-show="status !== 'waiting' && p.scoreDelta !== 0" x-transition
                                    class="text-[10px] font-bold font-mono mt-1"
                                    :class="p.scoreDelta < 0 ? 'text-red-400' : 'text-emerald-400'"
                                    x-text="(p.scoreDelta > 0 ? '+' : '') + formatScore(p.scoreDelta)"></div>
                            </div>
                        </li>
                    </template>
                </ul>

                <!-- Host Controls -->
                <div x-show="isHost && status === 'waiting'" class="mt-6">
                    <button @click="startGame" :disabled="loadingStart || loadingLeave"
                        class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 py-3 rounded-xl font-bold text-lg shadow-lg hover:shadow-green-500/25 transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span x-show="loadingStart" class="nb-btn-spinner"></span>
                        <span x-text="loadingStart ? 'Memulai…' : 'Start Game'"></span>
                    </button>
                    <p class="text-xs text-slate-400 text-center mt-2">New players won't be able to join</p>
                </div>
            </div>

            <!-- Main Area -->
            <div
                class="md:col-span-2 glass-panel rounded-2xl p-8 flex flex-col items-center justify-center min-h-[400px] relative">

                <!-- Waiting State -->
                <div x-show="status === 'waiting'" class="text-center animate-pulse">
                    <div class="text-6xl mb-4">⏳</div>
                    <h2 class="text-2xl font-bold text-slate-300 mb-2">Waiting for Host to start...</h2>
                    <p class="text-slate-400">Invite friends with code: <span
                            class="text-white font-bold">{{ $room->code }}</span></p>
                </div>

                <!-- Playing State -->
                <div x-show="status === 'playing'"
                    class="w-full flex flex-col items-center justify-center text-center relative">

                    <!-- Gambler's Shield Modal -->
                    <div x-show="showGamblerModal" x-cloak
                        class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/90 backdrop-blur-md">
                        <div @click.outside="showGamblerModal = false"
                            class="glass-panel p-8 rounded-3xl max-w-sm w-full border border-yellow-500/30 text-center relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/10 to-transparent"></div>
                            <div class="relative z-10">
                                <h2
                                    class="text-3xl font-black text-yellow-400 mb-2 drop-shadow-[0_0_8px_rgba(234,179,8,0.5)]">
                                    Tebak Dadu!</h2>
                                <p class="text-slate-300 mb-6 text-sm">Pilih Ganjil atau Genap. Benar = 0 Damage. Salah
                                    = 2x Damage!</p>

                                <div class="grid grid-cols-2 gap-4">
                                    <button @click="executeUseCard(activeCardIdToUse, { guess: 'odd' })"
                                        :disabled="isUsingCard"
                                        class="bg-indigo-600/50 hover:bg-indigo-500 border border-indigo-400 text-white font-bold py-4 rounded-xl transition-all shadow-[0_0_15px_rgba(79,70,229,0.3)] hover:shadow-[0_0_25px_rgba(79,70,229,0.6)]">
                                        GANJIL (1, 3, 5)
                                    </button>
                                    <button @click="executeUseCard(activeCardIdToUse, { guess: 'even' })"
                                        :disabled="isUsingCard"
                                        class="bg-rose-600/50 hover:bg-rose-500 border border-rose-400 text-white font-bold py-4 rounded-xl transition-all shadow-[0_0_15px_rgba(225,29,72,0.3)] hover:shadow-[0_0_25px_rgba(225,29,72,0.6)]">
                                        GENAP (2, 4, 6)
                                    </button>
                                </div>
                                <button @click="showGamblerModal = false"
                                    class="mt-4 text-slate-400 hover:text-white text-sm transition-colors">Batal
                                    Pakai</button>
                            </div>
                        </div>
                    </div>

                    <!-- Target Player Modal (Blood Sacrifice) -->
                    <div x-show="showTargetModal" x-cloak
                        class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/90 backdrop-blur-md">
                        <div @click.outside="showTargetModal = false"
                            class="glass-panel p-8 rounded-3xl max-w-sm w-full border border-red-500/30 text-center relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-red-500/10 to-transparent"></div>
                            <div class="relative z-10">
                                <h2
                                    class="text-3xl font-black text-red-400 mb-2 drop-shadow-[0_0_8px_rgba(248,113,113,0.5)]">
                                    Pilih Korban</h2>
                                <p class="text-slate-300 mb-6 text-sm">Siapa yang mau kamu jadikan target?</p>

                                <div class="flex flex-col gap-3 max-h-[40vh] overflow-y-auto pr-2">
                                    <template x-for="p in players.filter(pl => pl.id !== currentPlayerId)" :key="p.id">
                                        <button @click="executeUseCard(activeCardIdToUse, { target_player_id: p.id })"
                                            :disabled="isUsingCard"
                                            class="bg-slate-800/80 hover:bg-red-900/50 border border-slate-600 hover:border-red-500 text-white font-bold py-3 px-4 rounded-xl transition-all flex items-center justify-between gap-3">
                                            <span x-text="p.name.slice(0, 25)" class="truncate inline-block align-bottom" :title="p.name"></span>
                                            <span class="text-xs text-slate-400 shrink-0">Pilih Target</span>
                                        </button>
                                    </template>
                                    <div x-show="players.filter(pl => pl.id !== currentPlayerId).length === 0"
                                        class="text-slate-500 text-sm py-4">
                                        Gak ada pemain lain buat dijadiin korban.
                                    </div>
                                </div>
                                <button @click="showTargetModal = false"
                                    class="mt-6 text-slate-400 hover:text-white text-sm transition-colors">Batal
                                    Pakai</button>
                            </div>
                        </div>
                    </div>

                    <!-- Compact roll request feedback (DB round-trip) -->
                    <div x-show="isRolling" x-transition
                        class="absolute top-0 left-1/2 -translate-x-1/2 z-10 px-4 py-2 rounded-full bg-slate-900/90 border border-pink-500/30 text-pink-200 text-sm font-medium shadow-lg backdrop-blur-sm flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pink-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-pink-500"></span>
                        </span>
                        Mengirim lemparan ke server…
                    </div>

                    <div class="mb-12 mt-4 flex justify-center gap-6">
                        <div x-show="rollResultNotice.show" x-cloak
                            class="nb-roll-result-burst pointer-events-none absolute left-1/2 top-[38%] z-20 text-center">
                            <p class="text-xs uppercase tracking-[0.3em] text-yellow-200 font-black drop-shadow-lg">Rolled</p>
                            <div class="text-7xl md:text-8xl font-black text-yellow-300 drop-shadow-[0_0_26px_rgba(250,204,21,0.75)]"
                                x-text="rollResultNotice.value"></div>
                        </div>
                        <template x-for="(diceVal, idx) in visibleDiceValues()" :key="idx">
                            <div class="scene">
                                <!-- Alpine classes apply the 3D rotation logic -->
                                <div class="dice" :class="[
                                    isAnimating ? 'rolling' : '',
                                    diceVal > 0 && !isAnimating ? 'show-' + diceVal : 'show-1'
                                ]">
                                    <div class="dice-face face-1">
                                        <div class="dot" style="grid-area: 2/2"></div>
                                    </div>
                                <div class="dice-face face-2">
                                    <div class="dot" style="grid-area: 1/1"></div>
                                    <div class="dot" style="grid-area: 3/3"></div>
                                </div>
                                <div class="dice-face face-3">
                                    <div class="dot" style="grid-area: 1/1"></div>
                                    <div class="dot" style="grid-area: 2/2"></div>
                                    <div class="dot" style="grid-area: 3/3"></div>
                                </div>
                                <div class="dice-face face-4">
                                    <div class="dot" style="grid-area: 1/1"></div>
                                    <div class="dot" style="grid-area: 1/3"></div>
                                    <div class="dot" style="grid-area: 3/1"></div>
                                    <div class="dot" style="grid-area: 3/3"></div>
                                </div>
                                <div class="dice-face face-5">
                                    <div class="dot" style="grid-area: 1/1"></div>
                                    <div class="dot" style="grid-area: 1/3"></div>
                                    <div class="dot" style="grid-area: 2/2"></div>
                                    <div class="dot" style="grid-area: 3/1"></div>
                                    <div class="dot" style="grid-area: 3/3"></div>
                                </div>
                                <div class="dice-face face-6">
                                    <div class="dot" style="grid-area: 1/1"></div>
                                    <div class="dot" style="grid-area: 2/1"></div>
                                    <div class="dot" style="grid-area: 3/1"></div>
                                    <div class="dot" style="grid-area: 1/3"></div>
                                    <div class="dot" style="grid-area: 2/3"></div>
                                    <div class="dot" style="grid-area: 3/3"></div>
                                </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <h2 class="text-3xl font-extrabold mb-2 text-white">
                        <span x-show="currentTurn === currentPlayerId" class="text-green-400">It's Your Turn!</span>
                        <span x-show="currentTurn !== currentPlayerId">Waiting for <span class="text-violet-400 truncate max-w-[200px] inline-block align-bottom"
                                x-text="getCurrentPlayerName().slice(0, 25)" :title="getCurrentPlayerName()"></span>...</span>
                    </h2>
                    <p class="text-slate-400 mb-8" x-show="hasLastRoll()">
                        <span class="font-bold text-white truncate max-w-[200px] inline-block align-bottom" x-text="lastRollerName.slice(0, 25)" :title="lastRollerName"></span> just rolled a <span
                            class="font-bold text-yellow-400" x-text="recentDice.join(' & ')"></span>!
                    </p>

                    <div class="flex gap-3 mb-4">
                        <button x-show="mode !== 'survival'" @click="showShopModal = true"
                            class="px-5 py-2 rounded-full bg-indigo-500/20 border border-indigo-400/40 hover:bg-indigo-400/25 text-indigo-100 text-sm font-bold transition">
                            Shop
                        </button>
                        <button @click="showInventoryModal = true"
                            class="px-5 py-2 rounded-full bg-slate-500/20 border border-slate-300/30 hover:bg-slate-400/25 text-slate-100 text-sm font-bold transition">
                            Inventory
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 mb-4" x-show="currentTurn === currentPlayerId">
                        Lempar dadu dulu, kalau udah baru pencet <span class="text-emerald-300 font-semibold">Akhiri
                            Giliran</span>.
                    </p>

                    <button x-show="currentTurn === currentPlayerId" @click="rollDice"
                        :disabled="isRolling || isAnimating || (me() && me().hasRolledThisTurn)"
                        class="bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 text-white px-12 py-4 rounded-full font-bold text-2xl shadow-xl hover:shadow-pink-500/50 transition-all transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center gap-3">
                        <span x-show="isRolling" class="nb-btn-spinner"></span>
                        <span x-text="isRolling ? 'Rolling…' : 'ROLL DICE'"></span>
                    </button>

                    <button x-show="currentTurn === currentPlayerId" @click="endTurn"
                        :disabled="isEndingTurn || !me() || !me().hasRolledThisTurn"
                        class="mt-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-10 py-3 rounded-full font-bold text-lg shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                        <span x-show="isEndingTurn" class="nb-btn-spinner"></span>
                        <span x-text="isEndingTurn ? 'Mengakhiri…' : 'Akhiri Giliran'"></span>
                    </button>
                </div>

                <!-- Finished State -->
                <div x-show="status === 'finished'" class="w-full text-center" x-cloak>
                    <div class="text-6xl mb-6">🏆</div>
                    <h2
                        class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-yellow-600 mb-6 drop-shadow-lg">
                        Game Over!</h2>

                    <div class="bg-black/40 rounded-xl p-6 border border-yellow-500/30 max-w-md mx-auto">
                        <h3 class="text-xl text-yellow-400 font-bold mb-4">Leaderboard</h3>
                        <ul class="space-y-2 text-left">
                            <template x-for="(bp, index) in leaderboard" :key="index">
                                <li class="flex justify-between items-center py-2 border-b border-white/10 last:border-0"
                                    :class="{'text-yellow-300 transform scale-110 font-bold': index === 0}">
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl"
                                            x-text="index === 0 ? '👑' : (index === 1 ? '🥈' : (index === 2 ? '🥉' : ''))"></span>
                                        <span class="text-lg truncate max-w-[220px] inline-block align-bottom" x-text="bp.name.slice(0, 25)" :title="bp.name"></span>
                                    </div>
                                    <div class="text-right">
                                        <div
                                            class="text-[10px] text-yellow-500/70 font-normal uppercase tracking-wider mb-0.5">
                                            @yield('score_label', 'Score')</div>
                                        <div class="font-black text-xl leading-none" x-text="bp.score"></div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <a href="#" @click.prevent="leaveRoom" :class="loadingLeave ? 'opacity-50 pointer-events-none' : ''"
                        class="inline-flex items-center gap-2 mt-8 text-slate-400 hover:text-white transition underline">
                        <span x-show="loadingLeave" class="nb-btn-spinner"></span>
                        <span x-text="loadingLeave ? 'Keluar…' : 'Leave Room'"></span>
                    </a>
                </div>

            </div>
        </div>

        <!-- Shop Modal -->
        <div x-show="showShopModal" x-cloak @click.self="showShopModal = false"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="glass-panel rounded-2xl w-full max-w-2xl p-6 border border-indigo-400/30"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-2xl font-bold text-indigo-300">Shop Kartu Efek</h3>
                    <button @click="showShopModal = false" class="text-slate-300 hover:text-white">Tutup</button>
                </div>
                <p class="text-sm text-slate-400 mb-4">Belanja pake poin lo. Mau nekat, mau licik, terserah tongkrongan
                    lo.</p>
                <div
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-h-[70vh] overflow-y-auto pr-1 justify-center place-content-center mx-auto">
                    <template x-for="card in cardCatalog" :key="card.id">
                        <div @click="(!isBuyingCard && !card.not_available) ? buyCard(card.id) : null"
                            class="nb-card-shell transition-all duration-200 min-h-[140px] p-2.5 relative group mx-auto w-full max-w-[140px]"
                            :class="[
                                card.type === 'trap' ? 'trap' : 'spell',
                                card.not_available ? 'opacity-45 grayscale cursor-not-allowed' : 'cursor-pointer'
                            ]">
                            <div x-show="card.not_available" class="absolute inset-0 pointer-events-none overflow-hidden rounded-[10px] z-20">
                                <div class="absolute top-1/2 left-[-20%] w-[140%] border-t-4 border-white/90 -rotate-12"></div>
                            </div>
                            <div
                                class="absolute inset-0 bg-black/80 flex flex-col items-center justify-center transition-opacity z-10 rounded-lg"
                                :class="card.not_available ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'">
                                <span class="text-yellow-400 font-bold text-sm mb-1"
                                    x-text="card.not_available ? 'UNAVAILABLE' : (card.price + ' pts')"></span>
                                <span class="bg-indigo-600 text-white text-xs px-2.5 py-1 rounded"
                                    x-text="card.not_available ? 'DISABLED' : 'CLICK TO BUY'"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-black mb-2 text-slate-100">
                                <span x-text="card.name" class="truncate max-w-[100%]"></span>
                            </div>
                            <div class="nb-card-art h-[50px] mb-2"><span class="card-image"
                                    x-html="cardArtHtml(card, 'sm')"></span></div>
                            <div
                                class="nb-card-desc-box mt-0 p-1.5 h-[65px] overflow-hidden flex items-center justify-center text-center">
                                <p class="text-[9px] text-slate-200 leading-tight" x-text="card.description"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Inventory Modal -->
        <div x-show="showInventoryModal" x-cloak @click.self="showInventoryModal = false"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="glass-panel rounded-2xl w-full max-w-3xl p-6 border border-slate-400/30"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-2xl font-bold text-slate-100">Inventory</h3>
                    <button @click="showInventoryModal = false" class="text-slate-300 hover:text-white">Tutup</button>
                </div>
                <div
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-h-[70vh] overflow-y-auto pr-1 justify-center place-content-center mx-auto">
                    <template x-if="myInventory.length === 0">
                        <div
                            class="col-span-2 sm:col-span-3 md:col-span-4 rounded-xl border border-white/10 bg-slate-900/60 p-6 text-center text-slate-400 w-full">
                            Inventory lo kosong. Nabung poin dulu, beli kartu, baru rusuh.
                        </div>
                    </template>
                    <template x-for="(cid, index) in myInventory" :key="'mine-' + index">
                        <div @click="(!isUsingCard && canUseCard(cid)) ? useCard(cid) : null"
                            class="nb-card-shell cursor-pointer transition-all duration-200 min-h-[140px] p-2.5 relative group mx-auto w-full max-w-[140px]"
                            :class="[
                                ((cardCatalog.find(c => c.id === cid) || {}).type) === 'trap' ? 'trap' : 'spell',
                                (!isUsingCard && canUseCard(cid)) ? 'hover:scale-105' : 'opacity-50 grayscale cursor-not-allowed'
                             ]">
                            <div class="absolute inset-0 bg-black/80 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10 rounded-lg"
                                x-show="(!isUsingCard && canUseCard(cid))">
                                <span class="bg-emerald-600 text-white text-xs px-3 py-1.5 rounded font-bold">USE
                                    CARD</span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-black mb-2 text-slate-100">
                                <span x-text="(cardCatalog.find(c => c.id === cid) || {}).name || cid"
                                    class="truncate max-w-[100%]"></span>
                            </div>
                            <div class="nb-card-art h-[50px] mb-2"><span class="card-image"
                                    x-html="cardArtHtml(cardCatalog.find(c => c.id === cid) || {}, 'sm')"></span></div>
                            <div
                                class="nb-card-desc-box mt-0 p-1.5 h-[65px] overflow-hidden flex items-center justify-center text-center">
                                <p class="text-[9px] text-slate-200 leading-tight"
                                    x-text="(cardCatalog.find(c => c.id === cid) || {}).description"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>


        <!-- Kick Modal -->
        <div x-show="showKickModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-red-500/30 rounded-3xl p-8 max-w-sm w-full shadow-[0_0_50px_rgba(239,68,68,0.2)] text-center"
                x-transition:enter="transition ease-out duration-300 delay-100"
                x-transition:enter-start="opacity-0 scale-90 translate-y-8"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                <div class="text-7xl mb-6 animate-bounce">⚠️</div>
                <h2
                    class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-pink-500 mb-4">
                    Room Ditutup!</h2>
                <p class="text-slate-300 mb-8 leading-relaxed">Host telah meninggalkan permainan atau menutup Room ini
                    secara sepihak.</p>
                <a href="/"
                    class="block w-full bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-500 hover:to-rose-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-red-500/25 transition">
                    Kembali ke Beranda
                </a>
            </div>
        </div>

        <!-- Loadout Selection Modal (Survival Mode) -->
        <div x-show="status === 'selecting_cards'" x-cloak
            class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-slate-900/95 backdrop-blur-xl">
            <div
                class="w-full max-w-7xl h-full max-h-[92vh] flex flex-col glass-panel rounded-3xl p-6 border border-emerald-500/30 shadow-[0_0_80px_rgba(16,185,129,0.15)] relative">

                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
                    <div>
                        <h2
                            class="text-3xl md:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-400 mb-2">
                            Pilih Loadout Kartu</h2>
                        <p class="text-sm text-slate-400">Pilih maksimal <span class="text-emerald-300 font-bold">2
                                Spell</span> dan <span class="text-red-300 font-bold">2 Trap</span>. Klik kartu untuk
                            melihat preview, lalu tekan tombol pilih.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row md:flex-col xl:flex-row items-stretch sm:items-center md:items-stretch xl:items-center gap-3">
                        <div class="rounded-2xl border border-yellow-400/30 bg-yellow-500/10 px-5 py-3 text-right">
                            <p class="text-xs uppercase tracking-[0.2em] text-yellow-200">Waktu tersisa</p>
                            <p class="font-mono text-3xl font-bold text-yellow-300" x-text="formattedLoadoutTime()"></p>
                        </div>
                        <button @click="submitLoadout" :disabled="hasSelectedCards || isSubmittingLoadout"
                            class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white px-8 py-4 rounded-xl font-bold text-base shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <span
                                x-text="hasSelectedCards ? 'Menunggu...' : (isSubmittingLoadout ? 'Menyimpan...' : 'KUNCI LOADOUT')"></span>
                        </button>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-5 min-h-0 flex-1">
                    <div class="lg:w-2/3 min-h-0 flex flex-col rounded-2xl border border-white/10 bg-slate-950/35 overflow-hidden">
                        <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-slate-950/60 p-3">
                            <div class="inline-flex rounded-xl border border-white/10 bg-slate-900/80 p-1">
                                <button type="button" @click="loadoutTab = 'spell'; previewFirstLoadoutCard()"
                                    class="px-4 py-2 rounded-lg text-sm font-bold transition"
                                    :class="loadoutTab === 'spell' ? 'bg-emerald-500 text-white shadow-lg' : 'text-slate-300 hover:text-white'">
                                    Spell <span class="ml-1" x-text="'(' + selectedSpells.length + '/2)'"></span>
                                </button>
                                <button type="button" @click="loadoutTab = 'trap'; previewFirstLoadoutCard()"
                                    class="px-4 py-2 rounded-lg text-sm font-bold transition"
                                    :class="loadoutTab === 'trap' ? 'bg-red-500 text-white shadow-lg' : 'text-slate-300 hover:text-white'">
                                    Trap <span class="ml-1" x-text="'(' + selectedTraps.length + '/2)'"></span>
                                </button>
                            </div>
                            <p class="text-xs text-slate-400 hidden sm:block">Kartu terpilih: <span
                                    class="text-emerald-300 font-bold" x-text="selectedSpells.length"></span> spell,
                                <span class="text-red-300 font-bold" x-text="selectedTraps.length"></span> trap</p>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                                <template x-for="card in loadoutCards()" :key="card.id">
                                    <button type="button" @click="previewLoadoutCard(card)"
                                        class="nb-card-shell text-left cursor-pointer transition-all duration-200 min-h-[250px] p-3 hover:-translate-y-1"
                                        :class="[
                                            card.type === 'trap' ? 'trap' : 'spell',
                                            previewLoadoutCardId === card.id ? 'ring-4 ring-white/70' : '',
                                            isLoadoutSelected(card) ? (card.type === 'trap' ? 'ring-2 ring-red-300 bg-red-900/60' : 'ring-2 ring-emerald-300 bg-emerald-900/60') : '',
                                            !canSelectLoadoutCard(card) && !isLoadoutSelected(card) ? 'opacity-50 grayscale' : '',
                                            card.not_available ? 'opacity-45 grayscale cursor-not-allowed hover:translate-y-0' : ''
                                        ]">
                                        <div x-show="card.not_available"
                                            class="absolute inset-0 pointer-events-none overflow-hidden rounded-[10px] z-20">
                                            <div class="absolute top-1/2 left-[-20%] w-[140%] border-t-4 border-white/90 -rotate-12"></div>
                                        </div>
                                        <div class="flex items-center justify-between gap-2 text-sm font-black mb-2 text-slate-100">
                                            <span x-text="card.name" class="truncate"></span>
                                            <span x-show="isLoadoutSelected(card)"
                                                :class="card.type === 'trap' ? 'text-red-200' : 'text-emerald-200'">✓</span>
                                        </div>
                                        <div class="nb-card-art h-[115px] mb-3"><span class="card-image"
                                                x-html="cardArtHtml(card, 'md')"></span></div>
                                        <div class="nb-card-desc-box mt-0 p-2 h-[90px] overflow-hidden">
                                            <p class="text-xs text-slate-200 leading-snug" x-text="card.description"></p>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <aside class="lg:w-1/3 rounded-2xl border border-white/10 bg-slate-950/45 p-5 min-h-[420px] max-h-full overflow-y-auto flex flex-col">
                        <template x-if="selectedLoadoutCard()">
                            <div class="flex flex-col min-h-0 flex-1">
                                <div class="flex items-center justify-between gap-3 mb-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.2em]"
                                            :class="selectedLoadoutCard().type === 'trap' ? 'text-red-300' : 'text-emerald-300'"
                                            x-text="selectedLoadoutCard().type === 'trap' ? 'Trap' : 'Spell'"></p>
                                        <h3 class="text-2xl font-extrabold text-white leading-tight"
                                            x-text="selectedLoadoutCard().name"></h3>
                                    </div>
                                    <span x-show="isLoadoutSelected(selectedLoadoutCard())"
                                        class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-white">Dipilih</span>
                                </div>

                                <div class="nb-card-shell mx-auto w-full max-w-[240px] p-4 mb-5"
                                    :class="selectedLoadoutCard().type === 'trap' ? 'trap' : 'spell'">
                                    <div class="flex items-center justify-between text-base font-black mb-3 text-slate-100">
                                        <span x-text="selectedLoadoutCard().name" class="truncate"></span>
                                    </div>
                                    <div class="nb-card-art h-[135px] mb-3"><span class="card-image"
                                            x-html="cardArtHtml(selectedLoadoutCard(), 'lg')"></span></div>
                                    <div class="nb-card-desc-box mt-0 p-3 min-h-[95px]">
                                        <p class="text-xs text-slate-100 leading-relaxed"
                                            x-text="selectedLoadoutCard().description"></p>
                                    </div>
                                </div>

                                <div class="mt-auto space-y-3">
                                    <button type="button" @click="selectPreviewCard()"
                                        :disabled="hasSelectedCards || selectedLoadoutCard().not_available || (!isLoadoutSelected(selectedLoadoutCard()) && !canSelectLoadoutCard(selectedLoadoutCard()))"
                                        class="w-full rounded-xl px-5 py-3 font-bold text-white shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        :class="selectedLoadoutCard().type === 'trap'
                                            ? 'bg-red-600 hover:bg-red-500'
                                            : 'bg-emerald-600 hover:bg-emerald-500'"
                                        x-text="isLoadoutSelected(selectedLoadoutCard()) ? 'Batalkan Pilihan' : 'Pilih Kartu Ini'"></button>
                                    <p class="text-xs text-slate-400 text-center"
                                        x-text="selectedLoadoutCard().type === 'trap' ? 'Trap terpilih ' + selectedTraps.length + '/2' : 'Spell terpilih ' + selectedSpells.length + '/2'"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="!selectedLoadoutCard()">
                            <div class="flex flex-1 items-center justify-center text-center text-slate-400">
                                <p>Pilih salah satu kartu untuk melihat preview.</p>
                            </div>
                        </template>
                    </aside>
                </div>

                <div class="mt-5 flex items-center justify-center border-t border-white/10 pt-4 text-center">
                    <p class="text-sm text-slate-400">Game akan otomatis dimulai saat semua pemain mengunci loadout atau waktu habis.</p>
                </div>
            </div>
        </div>

        <!-- Action History Modal -->
        <div x-show="showHistoryModal" x-cloak @click.self="showHistoryModal = false"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="glass-panel rounded-2xl w-full max-w-2xl p-6 border border-violet-400/30 max-h-[80vh] flex flex-col"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-2xl font-bold text-violet-300">Action History</h3>
                    <button @click="showHistoryModal = false" class="text-slate-300 hover:text-white">Tutup</button>
                </div>
                <p class="text-xs text-slate-400 mb-3">Menampilkan semua aksi kecuali roll dice dan end turn.</p>
                <div class="flex-1 overflow-y-auto pr-1 space-y-2">
                    <template x-if="actionHistory.length === 0">
                        <div class="rounded-xl border border-white/10 bg-slate-900/60 p-4 text-center text-slate-400">
                            Belum ada aksi tercatat.
                        </div>
                    </template>
                    <template x-for="(entry, idx) in actionHistory" :key="'hist-' + idx">
                        <div class="rounded-xl border px-4 py-3"
                            :class="entry.type === 'trap'
                                ? 'border-red-400/40 bg-red-900/25'
                                : 'border-emerald-400/40 bg-emerald-900/25'">
                            <div class="text-[10px] uppercase tracking-wider text-slate-500" x-text="entry.time"></div>
                            <div class="text-sm text-slate-100 font-medium" x-text="entry.message"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

    <!-- Fireworks Canvas -->
    <canvas id="fireworks"
        class="absolute inset-0 pointer-events-none z-0 opacity-0 transition-opacity duration-1000"></canvas>

    <script>
        function gameClient() {
            return {
                roomCode: '{{ $room->code }}',
                mode: @json($room->mode ?? 'classic'),
                status: '{{ $room->status }}',
                // Versi Supabase/DB Lama:
                // currentPlayerId: {{ $currentPlayer->id }},
                // isHost: {{ $currentPlayer->is_host ? 'true' : 'false' }},
                // currentTurn: {{ $room->current_turn_player_id ?? 'null' }},

                // Versi Upstash Redis Baru:
                currentPlayerId: @json($currentPlayer->id ?? null),
                isHost: {{ (!empty($currentPlayer->is_host) && $currentPlayer->is_host) ? 'true' : 'false' }},
                currentTurn: @json($room->current_turn_player_id ?? null),
                currentRound: {{ $room->current_round ?? 1 }},
                totalRounds: {{ $room->total_rounds ?? 5 }},
                players: (@json($playersPublic)).map((p) => ({
                    ...p,
                    hasRolledThisTurn: !!p.has_rolled_this_turn,
                    displayScore: Number(p.score || 0),
                    scoreDelta: 0,
                    scoreAnimationFrame: null,
                })),

                getTrapTurns(player) {
                    if (!player || !player.active_buffs || player.active_buffs.length === 0) return 0;
                    let minTurns = 99;
                    let found = false;
                    player.active_buffs.forEach(buff => {
                        if (buff.startsWith('time_bomb:')) {
                            let t = parseInt(buff.split(':')[1]);
                            if (t < minTurns) minTurns = t;
                            found = true;
                        } else if ([
                            'curse_heavy_bones',
                            'forced_reroll',
                            'reverse_fortune',
                            'sabotaged',
                            'blindfold'
                        ].includes(buff)) {
                            if (1 < minTurns) minTurns = 1;
                            found = true;
                        }
                    });
                    return found ? minTurns : 0;
                },

                myInventory: @json($myInventory ?? []),
                recentDice: @json($room->last_dice_result ?? []),
                lastRollerName: @json($room->last_roller_name ?? ''),
                rollResultNotice: {
                    show: false,
                    value: '',
                    timeout: null
                },
                leaderboard: [],
                gameOverSequence: {
                    pending: false,
                    spotlight: false,
                    showLeaderboard: false,
                    leaderboard: [],
                    winner: null,
                    loser: null,
                    timeout: null
                },
                cardCatalog: @json($cardCatalog ?? []),
                showKickModal: false,
                showHistoryModal: false,
                isSkippingTrap: false,
                playerToKick: null,
                showGamblerModal: false,
                showTargetModal: false,
                activeCardIdToUse: null,
                loadingStart: false,
                loadingLeave: false,
                isRolling: false,
                isAnimating: false,
                pendingRollPlayers: null,
                pendingEffectPlayers: null,
                pendingEffectFlushTimeout: null,
                deferCardEffectPlayerSync: false,
                isEndingTurn: false,
                isBuyingCard: false,
                isUsingCard: false,
                showShopModal: false,
                showInventoryModal: false,
                pendingTrapConfirmations: @json($room->pending_trap_confirmations ?? []),
                // Versi Supabase/DB Lama:
                // trapTargetPlayerId: {{ $room->trap_target_player_id ?? 'null' }},

                // Versi Upstash Redis Baru:
                trapTargetPlayerId: @json($room->trap_target_player_id ?? null),
                isSkippingTrap: false,
                isSubmittingLoadout: false,
                selectionEndTime: @json($room->selection_end_time ?? null),
                serverTimeOffset: (@json(time())) - Math.floor(Date.now() / 1000),
                loadoutTimeLeft: 120,
                selectedSpells: [],
                selectedTraps: [],
                hasSelectedCards: false,
                loadoutTimer: null,
                loadoutAutoSubmitted: false,
                loadoutTab: 'spell',
                previewLoadoutCardId: null,
                actionHistory: [],
                targetRoulette: {
                    show: false,
                    locked: false,
                    cardId: null,
                    cardName: '',
                    selectedPlayer: null,
                    wheelPlayers: [],
                    segmentAngles: [],
                    wheelRotation: 0,
                    animationKey: 0,
                    lockTimeout: null,
                    timeout: null,
                },
                effectNotice: {
                    show: false,
                    type: 'spell',
                    icon: '✦',
                    cardName: '',
                    cardDescription: '',
                    cardArt: '',
                    cardStyle: '',
                    message: '',
                    usedByName: '',
                    targetName: '',
                    isRandom: false,
                    animationKey: 0,
                    timeout: null
                },
                toast: {
                    show: false,
                    message: '',
                    type: 'success',
                    timeout: null
                },

                initEcho() {
                    const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
                    const isPusher = '{{ config('broadcasting.default') }}' === 'pusher';

                    window.Echo = new Echo({
                        broadcaster: 'pusher',
                        // key: isPusher ? '{{ env('PUSHER_APP_KEY') }}' : 'numberbattlekey',
                        key: isPusher ? '{{ env('PUSHER_APP_KEY') }}' : '{{ env('REVERB_APP_KEY', 'numberbattlekey') }}',
                        cluster: isPusher ? '{{ env('PUSHER_APP_CLUSTER') }}' : 'mt1',
                        wsHost: isPusher ? undefined : window.location.hostname,
                        wsPort: isPusher ? undefined : 8080,
                        wssPort: isPusher ? undefined : 8080,
                        forceTLS: isPusher ? true : false,
                        encrypted: isPusher ? true : false,
                        disableStats: true,
                        enabledTransports: ['ws', 'wss'],
                        cluster: isPusher ? '{{ env('PUSHER_APP_CLUSTER') }}' : 'mt1'
                    });

                    window.Echo.channel('room.' + this.roomCode)
                        .listen('RoomStateUpdated', (e) => {
                            this.applyState(e.state);
                        })
                        .listen('DiceRolled', (e) => {
                            this.animateDice(e.diceResult, e.playerId, e.score);
                        })
                        .listen('CardEffectUsed', (e) => {
                            const p = e.payload || {};
                            let msg = p.note || (p.cardType === 'trap' ? 'Seseorang memakai trap!' : 'Seseorang memakai spell!');

                            if (p.isRandom) {
                                msg = '[Target Acak] ' + msg;
                            }

                            const showNotice = () => {
                                this.showEffectNotice(p, msg);
                                // Store exactly the same message shown in effect notice.
                                this.pushAction(msg, p.cardType || 'spell');
                            };

                            if (p.isRandom && p.targetPlayerId && p.usedByPlayerId !== this.currentPlayerId) {
                                this.startBroadcastTargetRoulette(p, showNotice);
                                return;
                            }

                            showNotice();
                        })
                        .listen('GameOver', (e) => {
                            this.prepareGameOver(e.leaderboard || []);
                        })
                        .listen('RoomClosed', () => {
                            this.showKickModal = true;
                            setTimeout(() => {
                                window.location.href = '/';
                            }, 4000);
                        })
                        .listen('PlayerLeft', (e) => {
                            this.players = this.players.filter(p => p.id !== e.playerId);
                        });

                    window.addEventListener('beforeunload', () => {
                        if (this.isHost || this.status === 'waiting') {
                            navigator.sendBeacon('/room/' + this.roomCode + '/leave', new URLSearchParams({
                                '_token': csrfToken
                            }));
                        }
                    });


                    // Trap modal is toggled by reactive status change
                    if (this.status === 'selecting_cards' && this.selectionEndTime) {
                        this.syncSelectedLoadoutFromInventory();
                        this.startLoadoutTimer();
                        this.previewFirstLoadoutCard();
                    }
                },

                applyState(state) {
                    if (!state) return;
                    this.mode = state.mode || this.mode;
                    const incomingStatus = state.status;
                    if (incomingStatus === 'finished' && !this.gameOverSequence.showLeaderboard) {
                        if (!this.gameOverSequence.pending) {
                            this.prepareGameOver([...(state.players || [])].sort((a, b) => Number(b.score || 0) - Number(a.score || 0)));
                        }
                    } else {
                        this.status = incomingStatus;
                    }
                    this.currentTurn = state.currentTurn;
                    this.currentRound = state.currentRound;
                    this.totalRounds = state.totalRounds;
                    this.turnHasSkip = state.turnHasSkip;
                    this.turnMultiplierPlayerId = state.turnMultiplierPlayerId;
                    this.pendingTrapConfirmations = state.pendingTrapConfirmations ?? [];
                    this.trapTargetPlayerId = state.trapTargetPlayerId;
                    if (this.isRolling || this.isAnimating) {
                        this.pendingRollPlayers = state.players ?? this.pendingRollPlayers;
                    } else if (this.effectNotice.show || this.deferCardEffectPlayerSync) {
                        this.pendingEffectPlayers = state.players ?? this.pendingEffectPlayers;
                    } else {
                        this.syncPlayers(state.players ?? this.players);
                    }
                    this.selectionEndTime = state.selectionEndTime ?? this.selectionEndTime;
                    if (state.serverTime) {
                        this.serverTimeOffset = state.serverTime - Math.floor(Date.now() / 1000);
                    }

                    const me = this.me();
                    if (me) {
                        this.hasSelectedCards = me.has_selected_cards;
                    }

                    if (!this.isAnimating && !this.isRolling) {
                        this.lastRollerName = state.lastRollerName || '';
                        let dr = state.lastDiceResult;
                        if (dr !== null && dr !== undefined) {
                            this.recentDice = Array.isArray(dr) ? dr : [dr];
                        } else {
                            this.recentDice = [];
                        }
                    }

                    if (this.status === 'selecting_cards' && this.selectionEndTime) {
                        this.syncSelectedLoadoutFromInventory();
                        this.startLoadoutTimer();
                        if (!this.selectedLoadoutCard()) {
                            this.previewFirstLoadoutCard();
                        }
                    }
                },

                triggerFireworks() {
                    const canvas = document.getElementById('fireworks');
                    canvas.classList.remove('opacity-0');
                    document.body.classList.add('bg-gradient-to-r', 'from-amber-500', 'to-red-600', 'animate-pulse');
                    setTimeout(() => document.body.classList.remove('animate-pulse'), 5000);
                },

                prepareGameOver(leaderboard) {
                    const sorted = [...(leaderboard || [])].sort((a, b) => Number(b.score || 0) - Number(a.score || 0));
                    if (sorted.length === 0) return;

                    this.gameOverSequence.pending = true;
                    this.gameOverSequence.showLeaderboard = false;
                    this.gameOverSequence.leaderboard = sorted;
                    this.gameOverSequence.winner = sorted[0] || null;
                    this.gameOverSequence.loser = sorted[sorted.length - 1] || null;

                    if (this.gameOverSequence.timeout) {
                        clearTimeout(this.gameOverSequence.timeout);
                    }

                    this.gameOverSequence.timeout = setTimeout(() => {
                        this.revealGameOver();
                    }, 4300);
                },

                revealGameOver() {
                    this.gameOverSequence.pending = false;
                    this.gameOverSequence.spotlight = true;
                    this.triggerFireworks();

                    setTimeout(() => {
                        this.leaderboard = this.gameOverSequence.leaderboard;
                        this.gameOverSequence.showLeaderboard = true;
                        this.status = 'finished';
                        this.gameOverSequence.spotlight = false;
                    }, 3200);
                },

                getCurrentPlayerName() {
                    const p = this.players.find(p => p.id === this.currentTurn);
                    return p ? p.name : 'Unknown';
                },

                me() {
                    return this.players.find(p => p.id === this.currentPlayerId) || null;
                },

                canOpenShop() {
                    return this.status === 'playing';
                },

                notify(message, type = 'success') {
                    if (this.toast.timeout) clearTimeout(this.toast.timeout);
                    this.toast.message = message;
                    this.toast.type = type;
                    this.toast.show = true;
                    this.toast.timeout = setTimeout(() => {
                        this.toast.show = false;
                    }, 2600);
                },

                pushAction(message, type = 'spell') {
                    const now = new Date();
                    const time = now.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    this.actionHistory.unshift({ time, message, type });
                    if (this.actionHistory.length > 120) {
                        this.actionHistory = this.actionHistory.slice(0, 120);
                    }
                },

                nameById(playerId) {
                    const player = this.players.find((p) => p.id === playerId);
                    return player ? player.name : null;
                },

                hasLastRoll() {
                    return !this.isAnimating && !!this.lastRollerName && this.recentDice.some((value) => Number(value) > 0);
                },

                visibleDiceValues() {
                    const values = Array.isArray(this.recentDice) ? this.recentDice : [];
                    return values.length > 0 ? values : [1];
                },

                escapeHtml(value) {
                    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;',
                    }[char]));
                },

                cardVisualPalette(card = {}, type = 'spell') {
                    const palettes = {
                        'bg-green-500': ['#22c55e', '#86efac', '#052e16', 'rgba(34, 197, 94, .62)'],
                        'bg-green-400': ['#4ade80', '#bbf7d0', '#064e3b', 'rgba(74, 222, 128, .62)'],
                        'bg-emerald-500': ['#10b981', '#6ee7b7', '#022c22', 'rgba(16, 185, 129, .64)'],
                        'bg-green-600': ['#16a34a', '#bef264', '#052e16', 'rgba(22, 163, 74, .62)'],
                        'bg-red-500': ['#ef4444', '#fb7185', '#450a0a', 'rgba(239, 68, 68, .66)'],
                        'bg-red-600': ['#dc2626', '#f97316', '#450a0a', 'rgba(220, 38, 38, .68)'],
                        'bg-red-700': ['#b91c1c', '#f43f5e', '#450a0a', 'rgba(185, 28, 28, .7)'],
                        'bg-red-800': ['#991b1b', '#fb923c', '#450a0a', 'rgba(153, 27, 27, .72)'],
                        'bg-blue-400': ['#60a5fa', '#22d3ee', '#172554', 'rgba(96, 165, 250, .62)'],
                        'bg-blue-300': ['#93c5fd', '#e0f2fe', '#172554', 'rgba(147, 197, 253, .6)'],
                        'bg-yellow-500': ['#eab308', '#fde047', '#422006', 'rgba(234, 179, 8, .64)'],
                        'bg-yellow-400': ['#facc15', '#fef08a', '#422006', 'rgba(250, 204, 21, .64)'],
                        'bg-indigo-500': ['#6366f1', '#a5b4fc', '#1e1b4b', 'rgba(99, 102, 241, .66)'],
                        'bg-indigo-600': ['#4f46e5', '#818cf8', '#1e1b4b', 'rgba(79, 70, 229, .68)'],
                        'bg-purple-400': ['#c084fc', '#f0abfc', '#3b0764', 'rgba(192, 132, 252, .62)'],
                        'bg-purple-600': ['#9333ea', '#c084fc', '#3b0764', 'rgba(147, 51, 234, .68)'],
                        'bg-gray-500': ['#64748b', '#cbd5e1', '#0f172a', 'rgba(100, 116, 139, .62)'],
                        'bg-gray-600': ['#475569', '#94a3b8', '#020617', 'rgba(71, 85, 105, .64)'],
                        'bg-gray-800': ['#1f2937', '#9ca3af', '#030712', 'rgba(31, 41, 55, .72)'],
                        'bg-orange-500': ['#f97316', '#fdba74', '#431407', 'rgba(249, 115, 22, .66)'],
                        'bg-orange-600': ['#ea580c', '#fb923c', '#431407', 'rgba(234, 88, 12, .68)'],
                        'bg-white': ['#f8fafc', '#bae6fd', '#334155', 'rgba(248, 250, 252, .58)'],
                        'bg-green-300': ['#86efac', '#dcfce7', '#14532d', 'rgba(134, 239, 172, .58)'],
                    };

                    const fallback = type === 'trap'
                        ? ['#fb7185', '#f97316', '#7f1d1d', 'rgba(248, 113, 113, .72)']
                        : ['#34d399', '#22d3ee', '#064e3b', 'rgba(52, 211, 153, .68)'];

                    const [primary, secondary, deep, glow] = palettes[card?.color] || fallback;
                    return { primary, secondary, deep, glow };
                },

                cardVisualStyle(card = {}, type = 'spell') {
                    const palette = this.cardVisualPalette(card, type);
                    return `--nb-effect-primary: ${palette.primary}; --nb-effect-secondary: ${palette.secondary}; --nb-effect-deep: ${palette.deep}; --nb-effect-glow: ${palette.glow}; --nb-card-primary: ${palette.primary}; --nb-card-secondary: ${palette.secondary}; --nb-card-glow: ${palette.glow};`;
                },

                cardArtHtml(card, size = 'md') {
                    if (card && card.image_url) {
                        return card.image_url;
                    }

                    const type = card?.type === 'trap' ? 'trap' : 'spell';
                    const safeSize = ['sm', 'md', 'lg'].includes(size) ? size : 'md';
                    const icon = this.escapeHtml(card?.icon || (type === 'trap' ? '☠️' : '✦'));

                    return `<span class="nb-card-icon-art nb-card-icon-art-${safeSize} ${type}"><span>${icon}</span></span>`;
                },

                effectCardArtHtml(card, type = 'spell') {
                    if (card && card.image_url) {
                        return card.image_url;
                    }

                    const safeCardId = this.escapeHtml(card?.id || 'unknown-card');
                    const safeName = this.escapeHtml(card?.name || 'Kartu');
                    const icon = this.escapeHtml(card?.icon || (type === 'trap' ? '☠️' : '✦'));
                    const style = this.cardVisualStyle(card, type);

                    return `<span class="nb-card-generated-art nb-card-effect-art ${type}" data-card-id="${safeCardId}" style="${style}"><span class="nb-card-effect-art-icon">${icon}</span><span class="nb-card-effect-art-name">${safeName}</span></span>`;
                },

                formatScore(value) {
                    return Math.round(Number(value || 0)).toLocaleString('id-ID');
                },

                syncPlayers(nextPlayers) {
                    const previousById = Object.fromEntries(this.players.map((player) => [player.id, player]));

                    this.players = (nextPlayers || []).map((nextPlayer) => {
                        const previous = previousById[nextPlayer.id];
                        const nextScore = Number(nextPlayer.score || 0);

                        if (!previous) {
                            return {
                                ...nextPlayer,
                                hasRolledThisTurn: !!(nextPlayer.hasRolledThisTurn ?? nextPlayer.has_rolled_this_turn),
                                displayScore: nextScore,
                                scoreDelta: 0,
                                scoreAnimationFrame: null,
                            };
                        }

                        const previousScore = Number(previous.score || 0);
                        const hasRunningAnimation = !!previous.scoreAnimationFrame;

                        Object.assign(previous, nextPlayer, {
                            hasRolledThisTurn: !!(nextPlayer.hasRolledThisTurn ?? nextPlayer.has_rolled_this_turn),
                            score: nextScore,
                        });

                        if (previousScore !== nextScore) {
                            this.animatePlayerScore(previous, nextScore);
                        } else if (!hasRunningAnimation && Number(previous.displayScore ?? nextScore) !== nextScore) {
                            this.animatePlayerScore(previous, nextScore);
                        }

                        return previous;
                    });
                },

                animatePlayerScore(player, targetScore) {
                    const startScore = Number(player.displayScore ?? player.score ?? 0);
                    const endScore = Number(targetScore || 0);
                    const delta = endScore - startScore;

                    if (player.scoreAnimationFrame) {
                        cancelAnimationFrame(player.scoreAnimationFrame);
                    }

                    player.scoreDelta = delta;

                    if (delta === 0) {
                        player.displayScore = endScore;
                        player.scoreDelta = 0;
                        return;
                    }

                    const duration = Math.min(1600, Math.max(700, Math.abs(delta) * 2));
                    const startTime = performance.now();

                    const tick = (now) => {
                        const progress = Math.min(1, (now - startTime) / duration);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        player.displayScore = Math.round(startScore + (delta * eased));

                        if (progress < 1) {
                            player.scoreAnimationFrame = requestAnimationFrame(tick);
                        } else {
                            player.displayScore = endScore;
                            player.scoreDelta = 0;
                            player.scoreAnimationFrame = null;
                        }
                    };

                    player.scoreAnimationFrame = requestAnimationFrame(tick);
                },

                canUseCard(cardId, ownerPlayerId = null) {
                    if (this.status !== 'playing' && this.status !== 'awaiting_trap_confirmation') return false;
                    if (ownerPlayerId !== null && ownerPlayerId !== this.currentPlayerId) return false;
                    if (!this.myInventory.includes(cardId)) return false;

                    if (cardId === 'skip_si') {
                        if (this.currentTurn === this.currentPlayerId && this.status === 'playing') return false;
                        if (this.turnHasSkip) return false;
                        return true;
                    }

                    // Only allow using cards (spells/traps) during the player's own turn
                    if (this.status === 'playing' && this.currentTurn !== this.currentPlayerId) {
                        return false;
                    }

                    if (cardId === 'multiplier') {
                        if (this.turnMultiplierPlayerId === this.currentPlayerId) return false;
                        return true;
                    }

                    // For dynamic Survival Mode cards, we let the backend handle specific validations
                    return true;
                },

                async postJson(url, body = {}) {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body)
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        throw new Error(data.error || 'Terjadi error.');
                    }
                    if (data.state) {
                        this.applyState(data.state);
                    }
                    if (Array.isArray(data.myInventory)) {
                        this.myInventory = data.myInventory;
                    }
                    return data;
                },

                async startGame() {
                    this.loadingStart = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/start');
                        this.notify('Game dimulai. Jangan ngantuk, gas!');
                    } catch (error) {
                        this.notify(error.message || 'Gagal memulai permainan.', 'error');
                    } finally {
                        this.loadingStart = false;
                    }
                },

                leaveRoom() {
                    this.loadingLeave = true;
                    this.postJson('/room/' + this.roomCode + '/leave')
                        .finally(() => {
                            window.location.href = '/';
                        });
                },

                async rollDice() {
                    this.isRolling = true;
                    try {
                        const res = await this.postJson('/room/' + this.roomCode + '/roll');
                        if (res && res.diceResult !== undefined) {
                            // animateDice is also triggered by Echo, but sometimes we get it directly
                            // if we don't receive Echo.
                        }
                    } catch (error) {
                        this.isRolling = false;
                        this.notify(error.message || 'Gagal melempar dadu.', 'error');
                    }
                },

                async endTurn() {
                    this.isEndingTurn = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/end-turn');
                        this.notify('Giliran kelar. Lanjut korban berikutnya.');
                    } catch (error) {
                        this.notify(error.message || 'Gagal mengakhiri giliran.', 'error');
                    } finally {
                        this.isEndingTurn = false;
                    }
                },

                async buyCard(cardId) {
                    this.isBuyingCard = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/shop/buy', { card_id: cardId });
                        this.notify('Mantap, kartu masuk inventory lo.');
                    } catch (error) {
                        this.notify(error.message || 'Gagal beli kartu.', 'error');
                    } finally {
                        this.isBuyingCard = false;
                    }
                },

                async useCard(cardId) {
                    if (!this.canUseCard(cardId, this.currentPlayerId)) {
                        this.notify('Timing kartu ini belum cocok, sabar dikit.', 'error');
                        return;
                    }

                    if (cardId === 'gamblers_shield') {
                        this.activeCardIdToUse = cardId;
                        this.showGamblerModal = true;
                        this.showInventoryModal = false;
                        return;
                    }

                    const cardData = this.cardCatalog.find(c => c.id === cardId);

                    const targetedCards = [
                        'curse_heavy_bones', 'blood_siphon',
                        'forced_reroll', 'poison_dart', 'karma',
                        'reverse_fortune', 'sabotage', 'time_bomb', 'blindfold'
                    ];

                    if (targetedCards.includes(cardId)) {
                        const otherPlayers = this.players.filter(p => p.id !== this.currentPlayerId);
                        if (otherPlayers.length > 0) {
                            this.showInventoryModal = false;
                            this.startTargetRoulette(cardId, otherPlayers);
                        } else {
                            this.notify('Tidak ada korban untuk ditarget!', 'error');
                        }
                        return;
                    }

                    this.executeUseCard(cardId, {});
                },

                targetInitial(player) {
                    const name = String(player?.name || '?').trim();
                    return name ? name.charAt(0).toUpperCase() : '?';
                },

                playersForTargetGacha(players, selectedPlayer) {
                    const others = players.filter(player => player.id !== selectedPlayer.id);
                    const shuffled = others.sort(() => Math.random() - 0.5);
                    const wheelPlayers = [selectedPlayer, ...shuffled].slice(0, 8);
                    const total = Math.max(1, wheelPlayers.length);
                    const selectedIndex = wheelPlayers.findIndex(player => player.id === selectedPlayer.id);
                    const segmentAngles = wheelPlayers.map((_, index) => (360 / total) * index);
                    const selectedAngle = segmentAngles[Math.max(0, selectedIndex)] || 0;

                    return {
                        wheelPlayers,
                        segmentAngles,
                        wheelRotation: 1800 - selectedAngle,
                    };
                },

                startTargetRoulette(cardId, candidatePlayers) {
                    if (this.targetRoulette.lockTimeout) {
                        clearTimeout(this.targetRoulette.lockTimeout);
                        this.targetRoulette.lockTimeout = null;
                    }
                    if (this.targetRoulette.timeout) {
                        clearTimeout(this.targetRoulette.timeout);
                        this.targetRoulette.timeout = null;
                    }

                    const card = this.cardCatalog.find(c => c.id === cardId) || {};
                    const selectedPlayer = candidatePlayers[Math.floor(Math.random() * candidatePlayers.length)];

                    this.isUsingCard = true;
                    this.targetRoulette.show = false;
                    this.targetRoulette.locked = false;
                    this.targetRoulette.cardId = cardId;
                    this.targetRoulette.cardName = card.name || cardId;
                    const gachaWheel = this.playersForTargetGacha(candidatePlayers, selectedPlayer);

                    this.targetRoulette.selectedPlayer = selectedPlayer;
                    this.targetRoulette.wheelPlayers = gachaWheel.wheelPlayers;
                    this.targetRoulette.segmentAngles = gachaWheel.segmentAngles;
                    this.targetRoulette.wheelRotation = gachaWheel.wheelRotation;
                    this.targetRoulette.animationKey += 1;

                    this.$nextTick(() => {
                        this.targetRoulette.show = true;
                    });

                    this.targetRoulette.lockTimeout = setTimeout(() => {
                        this.targetRoulette.locked = true;
                    }, 2050);

                    this.targetRoulette.timeout = setTimeout(() => {
                        this.targetRoulette.show = false;
                        this.targetRoulette.timeout = null;
                        this.targetRoulette.lockTimeout = null;
                        this.executeUseCard(cardId, { target_player_id: selectedPlayer.id, is_random: true });
                    }, 3050);
                },

                startBroadcastTargetRoulette(payload, onComplete = null) {
                    if (this.targetRoulette.lockTimeout) {
                        clearTimeout(this.targetRoulette.lockTimeout);
                        this.targetRoulette.lockTimeout = null;
                    }
                    if (this.targetRoulette.timeout) {
                        clearTimeout(this.targetRoulette.timeout);
                        this.targetRoulette.timeout = null;
                    }

                    const card = this.cardCatalog.find(c => c.id === payload.cardId) || {};
                    const selectedPlayer = this.players.find(player => player.id === payload.targetPlayerId) || {
                        id: payload.targetPlayerId,
                        name: payload.targetPlayerName || 'Target',
                    };
                    const candidatePlayers = this.players.filter(player => player.id !== payload.usedByPlayerId);
                    const gachaWheel = this.playersForTargetGacha(
                        candidatePlayers.some(player => player.id === selectedPlayer.id)
                            ? candidatePlayers
                            : [selectedPlayer, ...candidatePlayers],
                        selectedPlayer
                    );

                    this.targetRoulette.show = false;
                    this.targetRoulette.locked = false;
                    this.targetRoulette.cardId = payload.cardId;
                    this.targetRoulette.cardName = payload.cardName || card.name || payload.cardId || 'Kartu';
                    this.targetRoulette.selectedPlayer = selectedPlayer;
                    this.targetRoulette.wheelPlayers = gachaWheel.wheelPlayers;
                    this.targetRoulette.segmentAngles = gachaWheel.segmentAngles;
                    this.targetRoulette.wheelRotation = gachaWheel.wheelRotation;
                    this.targetRoulette.animationKey += 1;

                    this.$nextTick(() => {
                        this.targetRoulette.show = true;
                    });

                    this.targetRoulette.lockTimeout = setTimeout(() => {
                        this.targetRoulette.locked = true;
                    }, 2050);

                    this.targetRoulette.timeout = setTimeout(() => {
                        this.targetRoulette.show = false;
                        this.targetRoulette.timeout = null;
                        this.targetRoulette.lockTimeout = null;
                        if (typeof onComplete === 'function') {
                            onComplete();
                        }
                    }, 3050);
                },

                async executeUseCard(cardId, payload = {}) {
                    this.isUsingCard = true;
                    this.deferCardEffectPlayerSync = true;
                    this.showGamblerModal = false;
                    this.showTargetModal = false;
                    try {
                        const body = { card_id: cardId, ...payload };
                        await this.postJson('/room/' + this.roomCode + '/cards/use', body);
                        this.showInventoryModal = false;
                        this.showShopModal = false;
                        this.showGamblerModal = false;
                        this.showTargetModal = false;
                        this.notify('Kartu dipakai. Semoga musuh makin kesel.');
                    } catch (error) {
                        this.deferCardEffectPlayerSync = false;
                        this.notify(error.message || 'Gagal pakai kartu.', 'error');
                    } finally {
                        this.isUsingCard = false;
                        this.activeCardIdToUse = null;
                    }
                },

                cardCount(cardId) {
                    return this.myInventory.filter((c) => c === cardId).length;
                },



                showEffectNotice(payload, message) {
                    if (this.effectNotice.timeout) clearTimeout(this.effectNotice.timeout);
                    if (this.pendingEffectFlushTimeout) {
                        clearTimeout(this.pendingEffectFlushTimeout);
                        this.pendingEffectFlushTimeout = null;
                    }
                    if (this.effectNotice.show && this.pendingEffectPlayers) {
                        this.syncPlayers(this.pendingEffectPlayers);
                        this.pendingEffectPlayers = null;
                    }
                    const card = this.cardCatalog.find((item) => item.id === payload.cardId) || {};
                    const type = payload.cardType || card.type || 'spell';
                    this.effectNotice.show = false;
                    this.effectNotice.type = type;
                    this.effectNotice.icon = card.icon || (type === 'trap' ? '!' : '✦');
                    this.effectNotice.cardName = payload.cardName || card.name || 'Kartu';
                    this.effectNotice.cardDescription = card.description || 'Efek kartu berhasil dijalankan.';
                    const effectCard = { ...card, type, icon: card.icon || (type === 'trap' ? '☠️' : '✦') };
                    this.effectNotice.cardArt = this.effectCardArtHtml(effectCard, type);
                    this.effectNotice.cardStyle = this.cardVisualStyle(effectCard, type);
                    this.effectNotice.message = message;
                    this.effectNotice.usedByName = payload.usedByPlayerName || 'Pemain';
                    this.effectNotice.targetName = payload.targetPlayerName
                        || (payload.targetPlayerId ? this.nameById(payload.targetPlayerId) : null)
                        || (type === 'trap' ? 'Target tidak tercatat' : 'Diri sendiri / area efek');
                    this.effectNotice.isRandom = !!payload.isRandom;
                    this.effectNotice.animationKey += 1;
                    this.deferCardEffectPlayerSync = true;
                    this.$nextTick(() => {
                        this.effectNotice.show = true;
                    });
                    this.effectNotice.timeout = setTimeout(() => {
                        this.closeEffectNotice();
                    }, 7000);
                },

                closeEffectNotice() {
                    if (this.effectNotice.timeout) {
                        clearTimeout(this.effectNotice.timeout);
                        this.effectNotice.timeout = null;
                    }

                    this.effectNotice.show = false;
                    this.deferCardEffectPlayerSync = false;

                    if (this.pendingEffectFlushTimeout) {
                        clearTimeout(this.pendingEffectFlushTimeout);
                    }
                    this.pendingEffectFlushTimeout = setTimeout(() => {
                        if (this.pendingEffectPlayers) {
                            this.syncPlayers(this.pendingEffectPlayers);
                            this.pendingEffectPlayers = null;
                        }
                        this.pendingEffectFlushTimeout = null;
                    }, 180);
                },

                cardTypeClass(type) {
                    return type === 'trap'
                        ? 'border-red-400/40 bg-red-900/20'
                        : 'border-emerald-400/40 bg-emerald-900/20';
                },

                animateDice(result, pId, newScore) {
                    const pIndex = this.players.findIndex(p => p.id === pId);
                    const rollValues = Array.isArray(result) ? result : [result];
                    const rollerName = pIndex > -1 ? this.players[pIndex].name : '';
                    if (pIndex > -1) {
                        this.lastRollerName = '';
                    }

                    this.isAnimating = true;
                    this.isRolling = false;
                    this.recentDice = rollValues;

                    this.$nextTick(() => {
                        const diceEls = document.querySelectorAll('.dice');
                        diceEls.forEach(el => {
                            el.style.animation = 'none';
                            void el.offsetHeight;
                            el.style.animation = null;
                        });
                    });

                    setTimeout(() => {
                        this.isAnimating = false;
                        this.lastRollerName = rollerName;
                        this.recentDice = rollValues;
                        this.showRollResultNotice(rollValues);
                        if (this.pendingRollPlayers) {
                            setTimeout(() => {
                                this.syncPlayers(this.pendingRollPlayers);
                                this.pendingRollPlayers = null;
                            }, 450);
                        }
                    }, 1200);
                },

                showRollResultNotice(rollValues) {
                    if (this.rollResultNotice.timeout) clearTimeout(this.rollResultNotice.timeout);
                    this.rollResultNotice.value = rollValues.join(' + ');
                    this.rollResultNotice.show = false;
                    this.$nextTick(() => {
                        this.rollResultNotice.show = true;
                        this.rollResultNotice.timeout = setTimeout(() => {
                            this.rollResultNotice.show = false;
                        }, 1150);
                    });
                },

                startLoadoutTimer() {
                    if (this.loadoutTimer) clearInterval(this.loadoutTimer);
                    this.updateLoadoutTime();
                    this.loadoutTimer = setInterval(() => {
                        this.updateLoadoutTime();
                    }, 1000);
                },

                updateLoadoutTime() {
                    if (this.status !== 'selecting_cards') {
                        if (this.loadoutTimer) clearInterval(this.loadoutTimer);
                        return;
                    }
                    const serverNow = Math.floor(Date.now() / 1000) + this.serverTimeOffset;
                    this.loadoutTimeLeft = Math.max(0, (this.selectionEndTime || serverNow) - serverNow);

                    if (this.loadoutTimeLeft === 0 && !this.loadoutAutoSubmitted) {
                        if (this.loadoutTimer) clearInterval(this.loadoutTimer);
                        this.loadoutAutoSubmitted = true;
                        this.submitLoadout(true);
                    }
                },

                formattedLoadoutTime() {
                    const minutes = Math.floor(this.loadoutTimeLeft / 60);
                    const seconds = this.loadoutTimeLeft % 60;
                    return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                },

                loadoutCards() {
                    return this.cardCatalog.filter((card) => {
                        return card.type === this.loadoutTab && !['multiplier', 'skip_si'].includes(card.id);
                    });
                },

                syncSelectedLoadoutFromInventory() {
                    if (this.selectedSpells.length > 0 || this.selectedTraps.length > 0) return;
                    const cardsById = Object.fromEntries(this.cardCatalog.map((card) => [card.id, card]));
                    this.selectedSpells = this.myInventory
                        .filter((cardId) => cardsById[cardId]?.type === 'spell')
                        .slice(0, 2);
                    this.selectedTraps = this.myInventory
                        .filter((cardId) => cardsById[cardId]?.type === 'trap')
                        .slice(0, 2);
                },

                selectedLoadoutCard() {
                    return this.cardCatalog.find((card) => card.id === this.previewLoadoutCardId) || null;
                },

                previewFirstLoadoutCard() {
                    const cards = this.loadoutCards();
                    this.previewLoadoutCardId = cards.length > 0 ? cards[0].id : null;
                },

                previewLoadoutCard(card) {
                    if (!card || card.not_available) return;
                    this.previewLoadoutCardId = card.id;
                },

                isLoadoutSelected(card) {
                    if (!card) return false;
                    return card.type === 'spell'
                        ? this.selectedSpells.includes(card.id)
                        : this.selectedTraps.includes(card.id);
                },

                canSelectLoadoutCard(card) {
                    if (!card || card.not_available || this.hasSelectedCards) return false;
                    if (card.type === 'spell') {
                        return this.selectedSpells.length < 2 || this.selectedSpells.includes(card.id);
                    }
                    if (card.type === 'trap') {
                        return this.selectedTraps.length < 2 || this.selectedTraps.includes(card.id);
                    }
                    return false;
                },

                selectPreviewCard() {
                    const card = this.selectedLoadoutCard();
                    if (!card || !this.canSelectLoadoutCard(card)) return;
                    if (card.type === 'spell') {
                        if (this.selectedSpells.includes(card.id)) {
                            this.selectedSpells = this.selectedSpells.filter(id => id !== card.id);
                        } else if (this.selectedSpells.length < 2) {
                            this.selectedSpells = [...this.selectedSpells, card.id];
                        }
                    } else if (card.type === 'trap') {
                        if (this.selectedTraps.includes(card.id)) {
                            this.selectedTraps = this.selectedTraps.filter(id => id !== card.id);
                        } else if (this.selectedTraps.length < 2) {
                            this.selectedTraps = [...this.selectedTraps, card.id];
                        }
                    }
                },

                async submitLoadout(forceTimeout = false) {
                    if ((this.hasSelectedCards && !forceTimeout) || this.isSubmittingLoadout) return;
                    this.isSubmittingLoadout = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/submit-loadout', {
                            spells: this.selectedSpells,
                            traps: this.selectedTraps,
                            force_timeout: forceTimeout
                        });
                        this.hasSelectedCards = true;
                        if (!forceTimeout) {
                            this.notify('Loadout terkunci. Tunggu pemain lain.');
                        }
                    } catch (error) {
                        this.notify(error.message || 'Gagal menyimpan loadout.', 'error');
                    } finally {
                        this.isSubmittingLoadout = false;
                    }
                }
            };
        }
    </script>
</body>

</html>
