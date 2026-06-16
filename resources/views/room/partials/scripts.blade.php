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
                cardConfirm: {
                    show: false,
                    cardId: null,
                    card: null,
                },
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
                    timeout: null,
                    floatTimeout: null,
                    isFloating: false,
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
                        const safeUrl = this.escapeHtml(card.image_url);
                        const safeName = this.escapeHtml(card.name || 'Kartu');
                        return `<img src="${safeUrl}" alt="${safeName}" loading="eager" decoding="sync">`;
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

                openCardConfirm(cardId) {
                    const card = this.cardCatalog.find(c => c.id === cardId) || { id: cardId, name: cardId, type: 'spell' };
                    this.cardConfirm.cardId = cardId;
                    this.cardConfirm.card = card;
                    this.cardConfirm.show = true;
                },

                closeCardConfirm() {
                    this.cardConfirm.show = false;
                    this.cardConfirm.cardId = null;
                    this.cardConfirm.card = null;
                },

                async confirmUseCard() {
                    const cardId = this.cardConfirm.cardId;
                    if (!cardId) return;
                    this.closeCardConfirm();
                    await this.useCard(cardId);
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
                    this.effectNotice.isFloating = false;
                    if (this.effectNotice.floatTimeout) {
                        clearTimeout(this.effectNotice.floatTimeout);
                        this.effectNotice.floatTimeout = null;
                    }
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
                        this.effectNotice.floatTimeout = setTimeout(() => {
                            this.effectNotice.isFloating = true;
                            this.effectNotice.floatTimeout = null;
                        }, 1650);
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
                    if (this.effectNotice.floatTimeout) {
                        clearTimeout(this.effectNotice.floatTimeout);
                        this.effectNotice.floatTimeout = null;
                    }

                    this.effectNotice.show = false;
                    this.effectNotice.isFloating = false;
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
