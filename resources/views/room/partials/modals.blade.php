        <!-- Card Confirm Modal -->
        <div x-show="cardConfirm.show" x-cloak @click.self="closeCardConfirm()"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-950/82 backdrop-blur-md">
            <div class="relative w-full max-w-3xl overflow-hidden rounded-[2rem] border border-cyan-300/30 bg-gradient-to-br from-slate-950 via-slate-900 to-cyan-950/80 p-5 shadow-[0_0_80px_rgba(34,211,238,0.18)] md:p-6"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-95">
                <div class="absolute inset-x-0 top-0 h-1" :class="cardConfirm.card?.type === 'trap' ? 'bg-red-400' : 'bg-emerald-400'"></div>
                <button type="button" @click="closeCardConfirm()" class="absolute right-4 top-4 z-10 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-bold text-white/80 transition hover:bg-white/20 hover:text-white">Tutup</button>

                <div class="grid gap-5 md:grid-cols-[180px_1fr] md:items-center">
                    <div class="mx-auto w-[170px]">
                        <div class="nb-card-shell min-h-[250px] p-3" :class="cardConfirm.card?.type === 'trap' ? 'trap' : 'spell'">
                            <div class="mb-2 truncate text-xs font-black text-slate-100" x-text="cardConfirm.card?.name || cardConfirm.cardId || 'Kartu'"></div>
                            <div class="nb-card-art h-[110px] mb-2"><span class="card-image" x-html="cardArtHtml(cardConfirm.card || {}, 'md')"></span></div>
                            <div class="nb-card-desc-box text-[10px] leading-tight text-slate-200" x-text="cardConfirm.card?.description || 'Tidak ada deskripsi.'"></div>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-[0.35em]" :class="cardConfirm.card?.type === 'trap' ? 'text-red-200/80' : 'text-emerald-200/80'" x-text="cardConfirm.card?.type === 'trap' ? 'Trap Card Preview' : 'Spell Card Preview'"></p>
                        <h3 class="mt-2 text-3xl font-black leading-tight text-white md:text-5xl" x-text="cardConfirm.card?.name || cardConfirm.cardId || 'Kartu'"></h3>
                        <p class="mt-4 rounded-2xl border border-white/10 bg-black/30 p-4 text-sm leading-relaxed text-slate-200 md:text-base" x-text="cardConfirm.card?.description || 'Efek kartu akan divalidasi oleh server saat diaktifkan.'"></p>
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            <button type="button" @click="confirmUseCard()" :disabled="isUsingCard || !canUseCard(cardConfirm.cardId)" class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-500 to-emerald-500 px-6 py-3 font-black uppercase tracking-wider text-white shadow-lg shadow-cyan-500/20 transition hover:scale-[1.02] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100">
                                <span x-show="isUsingCard" class="nb-btn-spinner"></span>
                                <span x-text="isUsingCard ? 'Mengaktifkan…' : 'Aktifkan Kartu'"></span>
                            </button>
                        </div>
                    </div>
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
                        <div @click="openCardConfirm(cid)"
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
            class="loadout-overlay fixed inset-0 z-[140] flex items-center justify-center p-4">
            <div
                class="loadout-panel w-full max-w-7xl h-full max-h-[92vh] flex flex-col rounded-3xl bg-slate-950/92 p-6 border border-emerald-500/30 shadow-[0_22px_60px_rgba(0,0,0,0.38)] relative">

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
                            class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white px-8 py-4 rounded-xl font-bold text-base shadow-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
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

                        <div class="flex-1 overflow-y-auto p-4 overscroll-contain">
                            <div class="loadout-card-grid grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                                <template x-for="card in loadoutCards()" :key="card.id">
                                    <button type="button" @click="previewLoadoutCard(card)"
                                        class="loadout-card nb-card-shell text-left cursor-pointer min-h-[250px] p-3 hover:-translate-y-1"
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

                    <aside class="loadout-preview lg:w-1/3 rounded-2xl border border-white/10 bg-slate-950/45 p-5 min-h-[420px] max-h-full overflow-y-auto overscroll-contain flex flex-col">
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
