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

        <!-- 3D Card Battle Arena -->
        <div class="arena-wrap pb-10">
            <header class="mb-5 flex flex-col gap-4 rounded-[2rem] border border-cyan-300/20 bg-slate-950/70 px-5 py-4 shadow-2xl backdrop-blur-xl md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.38em] text-cyan-200/70">Sang Terpilih Arena</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-white md:text-5xl">
                        Room <span class="text-cyan-300">{{ $room->code }}</span>
                        @hasSection('mode_name')<span class="block text-sm uppercase tracking-[0.25em] text-amber-200 md:inline md:text-base">@yield('mode_name')</span>@endif
                    </h1>
                    <p class="mt-2 text-xs text-slate-400">Duel server-authoritative • Kamu: <span class="font-bold text-amber-200" title="{{ $currentPlayer->name }}">{{ Str::limit($currentPlayer->name, 25, '') }}</span></p>
                </div>
                <div class="flex flex-wrap items-center gap-2 md:justify-end">
                    <button @click="showHistoryModal = true" class="rounded-full border border-cyan-300/25 bg-cyan-300/10 px-4 py-2 text-xs font-black uppercase tracking-wider text-cyan-100 transition hover:bg-cyan-300/20">History</button>
                    <button x-show="mode !== 'survival' && status === 'playing'" @click="showShopModal = true" class="rounded-full border border-indigo-300/25 bg-indigo-400/10 px-4 py-2 text-xs font-black uppercase tracking-wider text-indigo-100 transition hover:bg-indigo-300/20">Shop</button>
                    <button @click="showInventoryModal = true" class="rounded-full border border-amber-300/30 bg-amber-300/10 px-4 py-2 text-xs font-black uppercase tracking-wider text-amber-100 transition hover:bg-amber-300/20">Inventory <span class="text-amber-300" x-text="myInventory.length"></span></button>
                    <button x-show="status === 'waiting'" @click="leaveRoom" :disabled="loadingLeave || loadingStart" class="inline-flex items-center gap-2 rounded-full border border-red-400/30 bg-red-500/10 px-4 py-2 text-xs font-black uppercase tracking-wider text-red-100 transition hover:bg-red-500/25 disabled:opacity-50">
                        <span x-show="loadingLeave" class="nb-btn-spinner"></span>
                        <span x-text="loadingLeave ? 'Keluar…' : 'Keluar Room'"></span>
                    </button>
                </div>
            </header>

            <section class="battle-board">
                <div class="arena-corner left-4 top-4 hidden md:block"></div>
                <div class="arena-corner right-4 top-4 hidden md:block"></div>
                <div class="arena-corner bottom-4 left-4 hidden md:block"></div>
                <div class="arena-corner bottom-4 right-4 hidden md:block"></div>

                <div class="relative z-10 grid min-h-[660px] grid-rows-[auto_1fr_auto] p-4 md:p-8">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <template x-for="(p, index) in players" :key="p.id">
                            <article class="player-token arena-card-avatar rounded-2xl p-3" :class="{'active ring-2 ring-cyan-300/80': status === 'playing' && currentTurn === p.id}">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-white/10 bg-gradient-to-br from-cyan-300/25 to-amber-300/10 text-lg font-black text-white" x-text="(p.name || '?').slice(0, 1).toUpperCase()"></div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="h-2.5 w-2.5 rounded-full" :class="p.id === currentTurn && status === 'playing' ? 'bg-cyan-300 animate-pulse' : 'bg-slate-600'"></span>
                                                <p class="truncate text-sm font-black text-white" x-text="p.name.slice(0, 25)" :title="p.name"></p>
                                                <span x-show="p.is_host" class="rounded bg-amber-400/15 px-1.5 py-0.5 text-[9px] font-black text-amber-200">HOST</span>
                                            </div>
                                            <p class="text-[10px] uppercase tracking-wider text-slate-400" x-show="p.id == currentPlayerId">Your seat</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[9px] uppercase tracking-[0.22em] text-slate-400">@yield('score_label', 'Score')</p>
                                        <p class="font-mono text-lg font-black tabular-nums" :class="p.scoreDelta < 0 ? 'text-red-300' : (p.scoreDelta > 0 ? 'text-emerald-300' : 'text-amber-200')" x-text="status !== 'waiting' ? formatScore(p.displayScore ?? p.score) : '-'"></p>
                                        <p class="score-delta-slot font-mono text-[10px] font-black" :class="p.scoreDelta < 0 ? 'text-red-400' : 'text-emerald-400'" x-text="status !== 'waiting' && p.scoreDelta !== 0 ? ((p.scoreDelta > 0 ? '+' : '') + formatScore(p.scoreDelta)) : ''"></p>
                                    </div>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1" x-show="p.active_buffs && p.active_buffs.length > 0">
                                    <template x-for="buff in p.active_buffs">
                                        <span class="rounded border border-cyan-200/15 bg-cyan-200/10 px-1.5 py-0.5 text-[9px] font-mono text-cyan-100" x-text="buff.split('_').join(' ').split(':').join(' ').toUpperCase()"></span>
                                    </template>
                                </div>
                                <template x-if="getTrapTurns(p) > 0">
                                    <div class="mt-2 inline-flex items-center gap-1 rounded-full border border-red-300/30 bg-red-500/15 px-2 py-1 text-[10px] font-black text-red-100">💣 Trap <span x-text="getTrapTurns(p)"></span></div>
                                </template>
                            </article>
                        </template>
                    </div>

                    <div class="relative flex items-center justify-center py-8 text-center">
                        <div x-show="status === 'waiting'" class="mx-auto max-w-xl rounded-[2rem] border border-cyan-300/25 bg-slate-950/65 p-8 shadow-2xl backdrop-blur-md">
                            <div class="text-7xl">🕯️</div>
                            <h2 class="mt-4 text-3xl font-black text-white">Arena menunggu imam...</h2>
                            <p class="mt-2 text-slate-300">Bagikan kode <span class="font-black text-cyan-200">{{ $room->code }}</span> ke pemain lain.</p>
                            <div x-show="isHost" class="mt-6">
                                <button @click="startGame" :disabled="loadingStart || loadingLeave" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-8 py-4 text-lg font-black text-white shadow-lg shadow-cyan-500/20 transition hover:scale-105 disabled:opacity-60">
                                    <span x-show="loadingStart" class="nb-btn-spinner"></span>
                                    <span x-text="loadingStart ? 'Memulai…' : 'Start Game'"></span>
                                </button>
                                <p class="mt-2 text-xs text-slate-400">New players won't be able to join</p>
                            </div>
                        </div>

                        <div x-show="status === 'playing'" class="relative w-full">
                            <!-- Gambler's Shield Modal -->
                            <div x-show="showGamblerModal" x-cloak class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/90 backdrop-blur-md">
                                <div @click.outside="showGamblerModal = false" class="glass-panel p-8 rounded-3xl max-w-sm w-full border border-yellow-500/30 text-center relative overflow-hidden">
                                    <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/10 to-transparent"></div>
                                    <div class="relative z-10">
                                        <h2 class="text-3xl font-black text-yellow-400 mb-2 drop-shadow-[0_0_8px_rgba(234,179,8,0.5)]">Tebak Dadu!</h2>
                                        <p class="text-slate-300 mb-6 text-sm">Pilih Ganjil atau Genap. Benar = 0 Damage. Salah = 2x Damage!</p>
                                        <div class="grid grid-cols-2 gap-4">
                                                            <button @click="executeUseCard(activeCardIdToUse, { guess: 'odd' })" :disabled="isUsingCard" class="bg-indigo-600/50 hover:bg-indigo-500 border border-indigo-400 text-white font-bold py-4 rounded-xl transition-colors">GANJIL</button>
                                                            <button @click="executeUseCard(activeCardIdToUse, { guess: 'even' })" :disabled="isUsingCard" class="bg-rose-600/50 hover:bg-rose-500 border border-rose-400 text-white font-bold py-4 rounded-xl transition-colors">GENAP</button>
                                        </div>
                                        <button @click="showGamblerModal = false" class="mt-4 text-slate-400 hover:text-white text-sm transition-colors">Batal Pakai</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Target Player Modal (Blood Sacrifice) -->
                            <div x-show="showTargetModal" x-cloak class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/90 backdrop-blur-md">
                                <div @click.outside="showTargetModal = false" class="glass-panel p-8 rounded-3xl max-w-sm w-full border border-red-500/30 text-center relative overflow-hidden">
                                    <div class="relative z-10">
                                        <h2 class="text-3xl font-black text-red-400 mb-2">Pilih Korban</h2>
                                        <p class="text-slate-300 mb-6 text-sm">Siapa yang mau kamu jadikan target?</p>
                                        <div class="flex flex-col gap-3 max-h-[40vh] overflow-y-auto pr-2">
                                            <template x-for="p in players.filter(pl => pl.id !== currentPlayerId)" :key="p.id">
                                                <button @click="executeUseCard(activeCardIdToUse, { target_player_id: p.id })" :disabled="isUsingCard" class="bg-slate-800/80 hover:bg-red-900/50 border border-slate-600 hover:border-red-500 text-white font-bold py-3 px-4 rounded-xl transition-colors flex items-center justify-between gap-3">
                                                    <span x-text="p.name.slice(0, 25)" class="truncate" :title="p.name"></span><span class="text-xs text-slate-400">Pilih Target</span>
                                                </button>
                                            </template>
                                        </div>
                                        <button @click="showTargetModal = false" class="mt-6 text-slate-400 hover:text-white text-sm">Batal Pakai</button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="isRolling" x-transition class="absolute left-1/2 top-0 z-20 flex -translate-x-1/2 items-center gap-2 rounded-full border border-cyan-300/30 bg-slate-950/90 px-4 py-2 text-sm font-bold text-cyan-100 shadow-lg backdrop-blur-sm"> <span class="h-2 w-2 animate-pulse rounded-full bg-cyan-300"></span> Mengirim lemparan ke server…</div>

                            <div x-show="rollResultNotice.show" x-cloak class="nb-roll-result-burst pointer-events-none absolute left-1/2 top-[16%] z-20 -translate-x-1/2 text-center">
                                <p class="text-xs uppercase tracking-[0.3em] text-yellow-200 font-black drop-shadow-lg">Rolled</p>
                                <div class="text-7xl md:text-8xl font-black text-yellow-300 drop-shadow-[0_0_26px_rgba(250,204,21,0.75)]" x-text="rollResultNotice.value"></div>
                            </div>

                            <div class="mx-auto flex min-h-[260px] max-w-2xl flex-col items-center justify-center rounded-[2rem] border border-cyan-200/15 bg-slate-950/20 p-6 backdrop-blur-[2px]">
                                <div class="flex justify-center gap-6">
                                    <template x-for="(diceVal, idx) in visibleDiceValues()" :key="idx">
                                        <div class="scene">
                                            <div class="dice" :class="[isAnimating ? 'rolling' : '', diceVal > 0 && !isAnimating ? 'show-' + diceVal : 'show-1']">
                                                <div class="dice-face face-1"><div class="dot" style="grid-area: 2/2"></div></div>
                                                <div class="dice-face face-2"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 3/3"></div></div>
                                                <div class="dice-face face-3"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 2/2"></div><div class="dot" style="grid-area: 3/3"></div></div>
                                                <div class="dice-face face-4"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 1/3"></div><div class="dot" style="grid-area: 3/1"></div><div class="dot" style="grid-area: 3/3"></div></div>
                                                <div class="dice-face face-5"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 1/3"></div><div class="dot" style="grid-area: 2/2"></div><div class="dot" style="grid-area: 3/1"></div><div class="dot" style="grid-area: 3/3"></div></div>
                                                <div class="dice-face face-6"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 2/1"></div><div class="dot" style="grid-area: 3/1"></div><div class="dot" style="grid-area: 1/3"></div><div class="dot" style="grid-area: 2/3"></div><div class="dot" style="grid-area: 3/3"></div></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <h2 class="mt-6 text-3xl font-black text-white">
                                    <span x-show="currentTurn === currentPlayerId" class="text-cyan-200">It's Your Turn!</span>
                                    <span x-show="currentTurn !== currentPlayerId">Waiting for <span class="text-amber-200" x-text="getCurrentPlayerName().slice(0, 25)" :title="getCurrentPlayerName()"></span>...</span>
                                </h2>
                                <p class="mt-2 text-slate-300" x-show="hasLastRoll()"><span class="font-bold text-white" x-text="lastRollerName.slice(0, 25)" :title="lastRollerName"></span> just rolled <span class="font-black text-yellow-300" x-text="recentDice.join(' & ')"></span>!</p>
                            </div>
                        </div>

                        <div x-show="status === 'finished'" x-cloak class="mx-auto max-w-2xl rounded-[2rem] border border-yellow-300/35 bg-slate-950/75 p-8 shadow-2xl">
                            <div class="text-7xl">🏆</div>
                            <h2 class="mt-3 text-5xl font-black text-yellow-200">Game Over!</h2>
                            <ul class="mt-6 space-y-2 text-left">
                                <template x-for="(bp, index) in leaderboard" :key="index">
                                    <li class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3" :class="{'text-yellow-200 ring-2 ring-yellow-300/50': index === 0}">
                                        <span class="font-black"><span x-text="index === 0 ? '👑' : (index === 1 ? '🥈' : (index === 2 ? '🥉' : ''))"></span> <span x-text="bp.name.slice(0, 25)" :title="bp.name"></span></span>
                                        <span class="font-mono text-xl font-black" x-text="bp.score"></span>
                                    </li>
                                </template>
                            </ul>
                            <a href="#" @click.prevent="leaveRoom" class="mt-6 inline-flex items-center gap-2 text-slate-300 underline hover:text-white"><span x-show="loadingLeave" class="nb-btn-spinner"></span><span x-text="loadingLeave ? 'Keluar…' : 'Leave Room'"></span></a>
                        </div>
                    </div>

                    <div class="hand-zone -mx-4 rounded-b-[38px] px-5 pt-4 pb-8 md:-mx-8 md:px-8 md:pb-10">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <p class="text-[10px] font-black uppercase tracking-[0.35em] text-cyan-100/70">Your Hand</p>
                                    <p class="text-xs text-slate-400" x-show="currentTurn === currentPlayerId">Roll dulu, lalu akhiri giliran.</p>
                                </div>
                                <div class="flex min-h-[210px] items-start gap-3 overflow-x-auto px-1 pt-4 pb-10">
                                    <template x-if="myInventory.length === 0">
                                        <div class="flex min-h-[120px] min-w-full items-center justify-center rounded-2xl border border-dashed border-white/15 bg-white/5 text-sm text-slate-400">Inventory kosong. Kartu yang kamu pilih akan tampil sebagai hand.</div>
                                    </template>
                                    <template x-for="(cid, index) in myInventory" :key="'hand-' + index">
                                        <button @click="openCardConfirm(cid)" class="hand-card nb-card-shell min-w-[92px] max-w-[110px] cursor-pointer p-2 text-left" :style="`--tilt: ${(index % 5 - 2) * 2.5}deg`" :class="[((cardCatalog.find(c => c.id === cid) || {}).type) === 'trap' ? 'trap' : 'spell', (!isUsingCard && canUseCard(cid)) ? '' : 'opacity-50 grayscale cursor-not-allowed']">
                                            <div class="mb-1 truncate text-[10px] font-black text-slate-100" x-text="(cardCatalog.find(c => c.id === cid) || {}).name || cid"></div>
                                            <div class="nb-card-art h-[54px] mb-1"><span class="card-image" x-html="cardArtHtml(cardCatalog.find(c => c.id === cid) || {}, 'sm')"></span></div>
                                            <p class="line-clamp-2 text-[8px] leading-tight text-slate-200" x-text="(cardCatalog.find(c => c.id === cid) || {}).description"></p>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col items-stretch gap-2 sm:flex-row lg:flex-col">
                                <button x-show="currentTurn === currentPlayerId" @click="rollDice" :disabled="isRolling || isAnimating || (me() && me().hasRolledThisTurn)" class="command-orb inline-flex items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-cyan-500 via-blue-500 to-amber-500 px-8 py-4 text-lg font-black uppercase tracking-wider text-white transition hover:scale-105 disabled:cursor-not-allowed disabled:opacity-50">
                                    <span x-show="isRolling" class="nb-btn-spinner"></span><span x-text="isRolling ? 'Rolling…' : 'ROLL DICE'"></span>
                                </button>
                                <button x-show="currentTurn === currentPlayerId" @click="endTurn" :disabled="isEndingTurn || !me() || !me().hasRolledThisTurn" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-7 py-3 font-black text-emerald-100 transition hover:bg-emerald-400/25 disabled:cursor-not-allowed disabled:opacity-50">
                                    <span x-show="isEndingTurn" class="nb-btn-spinner"></span><span x-text="isEndingTurn ? 'Mengakhiri…' : 'Akhiri Giliran'"></span>
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
