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
            class="modal-backdrop-lite fixed inset-0 z-[155] flex items-center justify-center p-3 md:p-4 bg-slate-950/88 overflow-y-auto">
            <div class="nb-effect-burst nb-effect-arena max-w-5xl w-full rounded-[2rem] border p-4 md:p-6 shadow-xl overflow-visible relative my-8"
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

                        <div class="nb-effect-card-3d" :class="{'is-floating': effectNotice.isFloating}" :key="effectNotice.animationKey">
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
