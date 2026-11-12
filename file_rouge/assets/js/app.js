/**
 * Application AutoLoc - Service de Location de Voitures
 * Architecture N-tiers Vanilla PHP / DAO JSON / Tailwind CSS / Vanilla JS (SoliCode)
 */

const API_BASE = "backend/api/api.php";

const app = {
    vueActuelle: "dashboard",
    donnees: {
        vehicules: [],
        categories: [],
        locataires: [],
        contrats: [],
        stats: {}
    },

    /**
     * Initialisation au chargement du DOM
     */
    async init() {
        console.log("Démarrage d'AutoLoc...");
        await this.rafraichir();
        this.naviguer("dashboard");
    },

    /**
     * Rafraîchit l'ensemble des données depuis l'API
     */
    async rafraichir() {
        await Promise.all([
            this.chargerStats(),
            this.chargerCategories(),
            this.chargerVehicules(),
            this.chargerLocataires(),
            this.chargerContrats()
        ]);
        this.mettreAJourBadges();
    },

    /**
     * Navigation entre les différentes vues (onglets)
     */
    naviguer(vue) {
        this.vueActuelle = vue;
        const vues = ["dashboard", "vehicules", "categories", "locataires", "contrats"];

        // Masquer toutes les sections
        vues.forEach(v => {
            const sec = document.getElementById(`vue-${v}`);
            const nav = document.getElementById(`nav-${v}`);
            if (sec) sec.classList.add("hidden");
            if (nav) {
                nav.classList.remove("bg-primary-600", "text-white", "shadow-sm");
                nav.classList.add("hover:bg-slate-800", "hover:text-white");
            }
        });

        // Afficher la section active
        const secActive = document.getElementById(`vue-${vue}`);
        const navActif = document.getElementById(`nav-${vue}`);
        if (secActive) secActive.classList.remove("hidden");
        if (navActif) {
            navActif.classList.remove("hover:bg-slate-800");
            navActif.classList.add("bg-primary-600", "text-white", "shadow-sm");
        }

        // Mettre à jour le titre et le bouton global
        const pageTitle = document.getElementById("page-title");
        const pageIcon = document.getElementById("page-icon");
        const btnTexte = document.getElementById("btn-nouveau-texte");

        switch (vue) {
            case "dashboard":
                pageTitle.textContent = "Tableau de bord";
                pageIcon.innerHTML = `<i class="fa-solid fa-chart-pie"></i>`;
                btnTexte.textContent = "Nouveau Contrat";
                this.rendreDashboard();
                break;
            case "vehicules":
                pageTitle.textContent = "Gestion des Véhicules";
                pageIcon.innerHTML = `<i class="fa-solid fa-car text-primary-600"></i>`;
                btnTexte.textContent = "Nouveau Véhicule";
                this.rendreVehicules();
                break;
            case "categories":
                pageTitle.textContent = "Catégories de Véhicules";
                pageIcon.innerHTML = `<i class="fa-solid fa-layer-group text-amber-500"></i>`;
                btnTexte.textContent = "Nouvelle Catégorie";
                this.rendreCategories();
                break;
            case "locataires":
                pageTitle.textContent = "Locataires (Clients)";
                pageIcon.innerHTML = `<i class="fa-solid fa-users text-emerald-500"></i>`;
                btnTexte.textContent = "Nouveau Locataire";
                this.rendreLocataires();
                break;
            case "contrats":
                pageTitle.textContent = "Contrats de Location";
                pageIcon.innerHTML = `<i class="fa-solid fa-file-contract text-purple-600"></i>`;
                btnTexte.textContent = "Nouveau Contrat";
                this.rendreContrats();
                break;
        }
    },

    /**
     * Action du bouton global "Nouveau ..." selon la vue active
     */
    actionNouveau() {
        switch (this.vueActuelle) {
            case "dashboard":
            case "contrats":
                this.ouvrirModal("modal-contrat");
                break;
            case "vehicules":
                this.ouvrirModal("modal-vehicule");
                break;
            case "categories":
                this.ouvrirModal("modal-categorie");
                break;
            case "locataires":
                this.ouvrirModal("modal-locataire");
                break;
        }
    },

    /**
     * Mettre à jour les badges de compteurs dans la barre latérale
     */
    mettreAJourBadges() {
        document.getElementById("badge-total-vehicules").textContent = this.donnees.vehicules.length;
        document.getElementById("badge-total-categories").textContent = this.donnees.categories.length;
        document.getElementById("badge-total-locataires").textContent = this.donnees.locataires.length;
        document.getElementById("badge-total-contrats").textContent = this.donnees.contrats.length;
    },

    // ==========================================
    // CHARGEMENT DES DONNÉES (FETCH API)
    // ==========================================

    async chargerStats() {
        try {
            const res = await fetch(`${API_BASE}?resource=stats`);
            const data = await res.json();
            if (data.success) {
                this.donnees.stats = data.data;
                this.rendreStats();
            }
        } catch (e) {
            console.error("Erreur stats:", e);
        }
    },

    async chargerCategories() {
        try {
            const res = await fetch(`${API_BASE}?resource=categories`);
            const data = await res.json();
            if (data.success) {
                this.donnees.categories = data.data;
                this.alimenterSelectCategories();
            }
        } catch (e) {
            this.Toast("Impossible de charger les catégories", "error");
        }
    },

    async chargerVehicules() {
        try {
            const res = await fetch(`${API_BASE}?resource=vehicules`);
            const data = await res.json();
            if (data.success) {
                this.donnees.vehicules = data.data;
                this.alimenterSelectVehiculesDisponibles();
            }
        } catch (e) {
            this.Toast("Impossible de charger les véhicules", "error");
        }
    },

    async chargerLocataires() {
        try {
            const res = await fetch(`${API_BASE}?resource=locataires`);
            const data = await res.json();
            if (data.success) {
                this.donnees.locataires = data.data;
                this.alimenterSelectLocataires();
            }
        } catch (e) {
            this.Toast("Impossible de charger les locataires", "error");
        }
    },

    async chargerContrats() {
        try {
            const res = await fetch(`${API_BASE}?resource=contrats`);
            const data = await res.json();
            if (data.success) {
                this.donnees.contrats = data.data;
            }
        } catch (e) {
            this.Toast("Impossible de charger les contrats", "error");
        }
    },

    // ==========================================
    // RENDU DES VUES (DOM DYNAMISÉ)
    // ==========================================

    rendreStats() {
        const s = this.donnees.stats;
        document.getElementById("stat-total-vehicules").textContent = s.totalVehicules || 0;
        document.getElementById("stat-vehicules-dispo").textContent = s.vehiculesDisponibles || 0;
        document.getElementById("stat-contrats-actifs").textContent = s.contratsEnCours || 0;
        document.getElementById("stat-chiffre-affaires").textContent = (s.chiffreAffaires || 0).toLocaleString("fr-FR") + " DH";
    },

    rendreDashboard() {
        this.rendreStats();
        const tbody = document.getElementById("tbody-dashboard-contrats");
        const recents = [...this.donnees.contrats].slice(-5).reverse();

        if (recents.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-slate-400">Aucun contrat enregistré.</td></tr>`;
            return;
        }

        tbody.innerHTML = recents.map(c => `
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3 px-6 font-mono font-medium text-slate-500">#${c.id}</td>
                <td class="py-3 px-6 font-semibold text-slate-800">${this.echapperHtml(c.locataireNom)} <span class="text-xs text-slate-400 block">${this.echapperHtml(c.locataireCin)}</span></td>
                <td class="py-3 px-6 font-medium text-slate-700">${this.echapperHtml(c.vehiculeLibelle)} <span class="text-xs font-mono text-slate-400 block">${this.echapperHtml(c.vehiculeImmatriculation)}</span></td>
                <td class="py-3 px-6 text-xs text-slate-500">${c.dateDebut} → ${c.dateFin} <span class="text-slate-400">(${c.dureeJours} j)</span></td>
                <td class="py-3 px-6 font-bold text-slate-900">${c.prixTotal.toLocaleString("fr-FR")} DH</td>
                <td class="py-3 px-6">${this.genererBadgeStatut(c.statut)}</td>
            </tr>
        `).join("");
    },

    rendreVehicules(liste = null) {
        const items = liste || this.donnees.vehicules;
        const tbody = document.getElementById("tbody-vehicules");

        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400">Aucun véhicule trouvé.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map(v => {
            const fallbackImg = "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=150&q=80";
            const carImg = v.image || fallbackImg;
            return `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="py-3.5 px-6 font-mono text-xs text-slate-400">#${v.id}</td>
                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">
                    <span class="px-2 py-1 rounded bg-slate-100 border border-slate-200 text-xs">${this.echapperHtml(v.immatriculation)}</span>
                </td>
                <td class="py-3.5 px-6">
                    <div class="flex items-center gap-3">
                        <img src="${carImg}" alt="${this.echapperHtml(v.marque)}" class="w-12 h-9 object-cover rounded-lg border border-slate-200 shadow-xs flex-shrink-0" onerror="this.src='${fallbackImg}'">
                        <div>
                            <span class="font-bold text-slate-800 text-sm block">${this.echapperHtml(v.marque)} ${this.echapperHtml(v.modele)}</span>
                        </div>
                    </div>
                </td>
                <td class="py-3.5 px-6"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/50">${this.echapperHtml(v.categorieNom || "Standard")}</span></td>
                <td class="py-3.5 px-6 font-bold text-slate-900">${v.prixJour.toLocaleString("fr-FR")} <span class="text-xs font-normal text-slate-400">DH / j</span></td>
                <td class="py-3.5 px-6">${this.genererBadgeStatut(v.statut)}</td>
                <td class="py-3.5 px-6 text-right">
                    <button onclick="app.supprimerVehicule(${v.id})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Supprimer">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </td>
            </tr>
            `;
        }).join("");
    },

    rendreCategories() {
        const conteneur = document.getElementById("grid-categories");
        if (this.donnees.categories.length === 0) {
            conteneur.innerHTML = `<div class="col-span-3 text-center py-8 text-slate-400">Aucune catégorie enregistrée.</div>`;
            return;
        }

        const fallbackCatImg = "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=800&q=80";

        conteneur.innerHTML = this.donnees.categories.map(c => {
            const vehiculesCat = this.donnees.vehicules.filter(v => v.categorieId === c.id);
            const catImage = c.image || fallbackCatImg;

            return `
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:border-slate-300 transition-all duration-200">
                <!-- Bannière Image de la Catégorie -->
                <div class="relative h-40 w-full overflow-hidden bg-slate-900">
                    <img src="${catImage}" alt="${this.echapperHtml(c.nom)}" class="w-full h-full object-cover opacity-85 hover:scale-105 transition-transform duration-500" onerror="this.src='${fallbackCatImg}'">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/30 to-transparent"></div>
                    
                    <!-- Bouton Supprimer & Badge Tarif -->
                    <div class="absolute top-3 right-3 flex items-center gap-2">
                        <button onclick="app.supprimerCategorie(${c.id})" class="w-8 h-8 rounded-full bg-slate-900/60 backdrop-blur-md text-white hover:bg-rose-600 transition-colors flex items-center justify-center text-xs" title="Supprimer catégorie">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </div>

                    <div class="absolute bottom-3 left-4 right-4 flex items-end justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded-full border border-amber-400/30">Catégorie</span>
                            <h4 class="text-xl font-extrabold text-white mt-1 drop-shadow-sm">${this.echapperHtml(c.nom)}</h4>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-300 block">Dès</span>
                            <span class="text-base font-black text-white">${c.tarifBase.toLocaleString("fr-FR")} DH<span class="text-xs font-normal text-slate-300">/j</span></span>
                        </div>
                    </div>
                </div>

                <!-- Corps de la Catégorie -->
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <p class="text-xs text-slate-600 line-clamp-2">${this.echapperHtml(c.description || "Aucune description")}</p>

                    <!-- Section des Véhicules de cette Catégorie avec leurs Images -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-car text-primary-600 text-xs"></i>
                                Véhicules associés (${vehiculesCat.length})
                            </span>
                            <button onclick="app.ouvrirModalVehiculePourCategorie(${c.id})" class="text-[11px] font-semibold text-primary-600 hover:text-primary-700 flex items-center gap-1">
                                <i class="fa-solid fa-plus text-[9px]"></i> Ajouter
                            </button>
                        </div>

                        ${vehiculesCat.length === 0 ? `
                            <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-400">
                                Aucun véhicule dans cette catégorie.
                            </div>
                        ` : `
                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                ${vehiculesCat.map(v => {
                                    const vImg = v.image || "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=150&q=80";
                                    return `
                                    <div class="flex items-center gap-3 p-2 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 hover:bg-slate-100/60 transition-all">
                                        <img src="${vImg}" alt="${this.echapperHtml(v.marque)}" class="w-14 h-10 object-cover rounded-lg border border-slate-200 flex-shrink-0 shadow-2xs" onerror="this.src='https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=150&q=80'">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <h5 class="text-xs font-bold text-slate-800 truncate">${this.echapperHtml(v.marque)} ${this.echapperHtml(v.modele)}</h5>
                                                <span class="text-xs font-black text-slate-900 ml-1 whitespace-nowrap">${v.prixJour} DH</span>
                                            </div>
                                            <div class="flex items-center justify-between mt-1">
                                                <span class="text-[10px] font-mono text-slate-400 px-1 rounded bg-white border border-slate-200">${this.echapperHtml(v.immatriculation)}</span>
                                                ${this.genererBadgeStatut(v.statut)}
                                            </div>
                                        </div>
                                    </div>
                                    `;
                                }).join("")}
                            </div>
                        `}
                    </div>
                </div>

                <!-- Footer de la Card -->
                <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>${vehiculesCat.filter(v => v.statut === "Disponible").length} disponible(s)</span>
                    <span class="font-medium text-slate-700">Flotte active</span>
                </div>
            </div>
            `;
        }).join("");
    },

    rendreLocataires(liste = null) {
        const items = liste || this.donnees.locataires;
        const tbody = document.getElementById("tbody-locataires");

        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400">Aucun locataire trouvé.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map(l => `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="py-3.5 px-6 font-mono text-xs text-slate-400">#${l.id}</td>
                <td class="py-3.5 px-6 font-mono font-bold text-slate-800">${this.echapperHtml(l.cin)}</td>
                <td class="py-3.5 px-6 font-semibold text-slate-900">${this.echapperHtml(l.nom)} ${this.echapperHtml(l.prenom)}</td>
                <td class="py-3.5 px-6 text-slate-600"><i class="fa-solid fa-phone text-xs text-slate-400 mr-1.5"></i>${this.echapperHtml(l.telephone)}</td>
                <td class="py-3.5 px-6 text-slate-600 text-xs">${this.echapperHtml(l.email || "—")}</td>
                <td class="py-3.5 px-6 font-mono text-xs text-slate-500">${this.echapperHtml(l.permis)}</td>
                <td class="py-3.5 px-6 text-right">
                    <button onclick="app.supprimerLocataire(${l.id})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Supprimer">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </td>
            </tr>
        `).join("");
    },

    rendreContrats(liste = null) {
        const items = liste || this.donnees.contrats;
        const tbody = document.getElementById("tbody-contrats");

        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-slate-400">Aucun contrat de location.</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map(c => `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="py-3.5 px-6 font-mono font-bold text-purple-700">#CTR-${c.id}</td>
                <td class="py-3.5 px-6 font-semibold text-slate-800">${this.echapperHtml(c.locataireNom)} <span class="text-xs text-slate-400 block font-mono">${this.echapperHtml(c.locataireCin)}</span></td>
                <td class="py-3.5 px-6 font-medium text-slate-800">${this.echapperHtml(c.vehiculeLibelle)} <span class="text-xs text-slate-400 block font-mono">${this.echapperHtml(c.vehiculeImmatriculation)}</span></td>
                <td class="py-3.5 px-6 text-xs text-slate-600">${c.dateDebut}</td>
                <td class="py-3.5 px-6 text-xs text-slate-600">${c.dateFin}</td>
                <td class="py-3.5 px-6 text-xs font-semibold text-slate-700">${c.dureeJours} jour(s)</td>
                <td class="py-3.5 px-6 font-extrabold text-slate-900">${c.prixTotal.toLocaleString("fr-FR")} DH</td>
                <td class="py-3.5 px-6">${this.genererBadgeStatut(c.statut)}</td>
                <td class="py-3.5 px-6 text-right space-x-2">
                    ${c.statut === "En cours" ? `
                        <button onclick="app.cloturerContrat(${c.id})" class="px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 rounded-lg transition-colors inline-flex items-center gap-1">
                            <i class="fa-solid fa-check text-[10px]"></i> Clôturer
                        </button>
                    ` : ''}
                    <button onclick="app.supprimerContrat(${c.id})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Supprimer">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </td>
            </tr>
        `).join("");
    },

    // ==========================================
    // SOUMISSION DES FORMULAIRES (POST)
    // ==========================================

    async soumettreCategorie(event) {
        event.preventDefault();
        const btn = document.getElementById("btn-submit-categorie");
        this.Loading(btn, true);

        const form = event.target;
        const payload = {
            nom: form.nom.value,
            tarifBase: parseFloat(form.tarifBase.value),
            description: form.description.value,
            image: form.image ? form.image.value : ""
        };

        try {
            const res = await fetch(`${API_BASE}?resource=categories`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                this.Toast("Catégorie créée avec succès !", "success");
                form.reset();
                this.fermerModal("modal-categorie");
                await this.chargerCategories();
                this.mettreAJourBadges();
                if (this.vueActuelle === "categories") this.rendreCategories();
            } else {
                this.Toast(data.message || "Erreur lors de la création", "error");
            }
        } catch (e) {
            this.Toast("Erreur réseau", "error");
        } finally {
            this.Loading(btn, false);
        }
    },

    async soumettreVehicule(event) {
        event.preventDefault();
        const btn = document.getElementById("btn-submit-vehicule");
        this.Loading(btn, true);

        const form = event.target;
        const payload = {
            immatriculation: form.immatriculation.value,
            marque: form.marque.value,
            modele: form.modele.value,
            categorieId: parseInt(form.categorieId.value),
            prixJour: parseFloat(form.prixJour.value),
            statut: form.statut.value,
            image: form.image ? form.image.value : ""
        };

        try {
            const res = await fetch(`${API_BASE}?resource=vehicules`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                this.Toast("Véhicule ajouté avec succès !", "success");
                form.reset();
                this.fermerModal("modal-vehicule");
                await this.chargerVehicules();
                await this.chargerStats();
                this.mettreAJourBadges();
                if (this.vueActuelle === "vehicules") this.rendreVehicules();
            } else {
                this.Toast(data.message || "Erreur lors de l'enregistrement", "error");
            }
        } catch (e) {
            this.Toast("Erreur réseau", "error");
        } finally {
            this.Loading(btn, false);
        }
    },

    async soumettreLocataire(event) {
        event.preventDefault();
        const btn = document.getElementById("btn-submit-locataire");
        this.Loading(btn, true);

        const form = event.target;
        const payload = {
            cin: form.cin.value,
            nom: form.nom.value,
            prenom: form.prenom.value,
            telephone: form.telephone.value,
            email: form.email.value,
            permis: form.permis.value
        };

        try {
            const res = await fetch(`${API_BASE}?resource=locataires`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                this.Toast("Locataire enregistré avec succès !", "success");
                form.reset();
                this.fermerModal("modal-locataire");
                await this.chargerLocataires();
                this.mettreAJourBadges();
                if (this.vueActuelle === "locataires") this.rendreLocataires();
            } else {
                this.Toast(data.message || "Erreur lors de l'enregistrement", "error");
            }
        } catch (e) {
            this.Toast("Erreur réseau", "error");
        } finally {
            this.Loading(btn, false);
        }
    },

    async soumettreContrat(event) {
        event.preventDefault();
        const btn = document.getElementById("btn-submit-contrat");
        this.Loading(btn, true);

        const form = event.target;
        const payload = {
            locataireId: parseInt(form.locataireId.value),
            vehiculeId: parseInt(form.vehiculeId.value),
            dateDebut: form.dateDebut.value,
            dateFin: form.dateFin.value
        };

        try {
            const res = await fetch(`${API_BASE}?resource=contrats`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                this.Toast("Contrat de location validé avec succès !", "success");
                form.reset();
                document.getElementById("recap-montant").textContent = "0.00 DH";
                document.getElementById("recap-duree").textContent = "0 jour(s) de location";
                this.fermerModal("modal-contrat");
                await this.rafraichir();
                this.naviguer("contrats");
            } else {
                this.Toast(data.message || "Erreur lors de la création du contrat", "error");
            }
        } catch (e) {
            this.Toast("Erreur réseau", "error");
        } finally {
            this.Loading(btn, false);
        }
    },

    // ==========================================
    // CLÔTURE ET SUPPRESSIONS
    // ==========================================

    async cloturerContrat(id) {
        if (!confirm("Voulez-vous clôturer ce contrat ? Le véhicule sera automatiquement remis à l'état 'Disponible'.")) return;

        try {
            const res = await fetch(`${API_BASE}?resource=contrats&action=cloturer&id=${id}`, {
                method: "POST"
            });
            const data = await res.json();
            if (data.success) {
                this.Toast(data.message, "success");
                await this.rafraichir();
                this.naviguer(this.vueActuelle);
            } else {
                this.Toast(data.message, "error");
            }
        } catch (e) {
            this.Toast("Erreur lors de la clôture", "error");
        }
    },

    async supprimerVehicule(id) {
        if (!confirm("Voulez-vous vraiment supprimer ce véhicule ?")) return;
        try {
            const res = await fetch(`${API_BASE}?resource=vehicules&id=${id}`, { method: "DELETE" });
            const data = await res.json();
            if (data.success) {
                this.Toast("Véhicule supprimé", "success");
                await this.chargerVehicules();
                await this.chargerStats();
                this.mettreAJourBadges();
                this.rendreVehicules();
            } else {
                this.Toast(data.message, "error");
            }
        } catch (e) {
            this.Toast("Erreur réseau", "error");
        }
    },

    async supprimerCategorie(id) {
        if (!confirm("Voulez-vous vraiment supprimer cette catégorie ?")) return;
        try {
            const res = await fetch(`${API_BASE}?resource=categories&id=${id}`, { method: "DELETE" });
            const data = await res.json();
            if (data.success) {
                this.Toast("Catégorie supprimée", "success");
                await this.chargerCategories();
                this.mettreAJourBadges();
                this.rendreCategories();
            } else {
                this.Toast(data.message, "error");
            }
        } catch (e) {
            this.Toast("Erreur réseau", "error");
        }
    },

    async supprimerLocataire(id) {
        if (!confirm("Voulez-vous vraiment supprimer ce locataire ?")) return;
        try {
            const res = await fetch(`${API_BASE}?resource=locataires&id=${id}`, { method: "DELETE" });
            const data = await res.json();
            if (data.success) {
                this.Toast("Locataire supprimé", "success");
                await this.chargerLocataires();
                this.mettreAJourBadges();
                this.rendreLocataires();
            } else {
                this.Toast(data.message, "error");
            }
        } catch (e) {
            this.Toast("Erreur réseau", "error");
        }
    },

    async supprimerContrat(id) {
        if (!confirm("Voulez-vous vraiment supprimer ce contrat ?")) return;
        try {
            const res = await fetch(`${API_BASE}?resource=contrats&id=${id}`, { method: "DELETE" });
            const data = await res.json();
            if (data.success) {
                this.Toast("Contrat supprimé", "success");
                await this.rafraichir();
                this.rendreContrats();
            } else {
                this.Toast(data.message, "error");
            }
        } catch (e) {
            this.Toast("Erreur réseau", "error");
        }
    },

    // ==========================================
    // LOGIQUE DE CALCUL ET ALIMENTATION FORMULAIRES
    // ==========================================

    calculerMontantContrat() {
        const selectVehicule = document.getElementById("select-modal-vehicule");
        const dateDebut = document.getElementById("contrat-date-debut").value;
        const dateFin = document.getElementById("contrat-date-fin").value;
        const recapMontant = document.getElementById("recap-montant");
        const recapDuree = document.getElementById("recap-duree");

        const opt = selectVehicule.options[selectVehicule.selectedIndex];
        const prixJour = opt ? parseFloat(opt.dataset.prix || 0) : 0;

        if (!dateDebut || !dateFin || prixJour <= 0) {
            recapMontant.textContent = "0.00 DH";
            recapDuree.textContent = "0 jour(s) de location";
            return;
        }

        const debut = new Date(dateDebut);
        const fin = new Date(dateFin);

        if (fin < debut) {
            recapMontant.textContent = "Invalide";
            recapDuree.textContent = "La date de fin doit être >= au début";
            return;
        }

        const diffTime = Math.abs(fin - debut);
        const diffDays = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
        const total = diffDays * prixJour;

        recapDuree.textContent = `${diffDays} jour(s) × ${prixJour} DH`;
        recapMontant.textContent = `${total.toLocaleString("fr-FR")} DH`;
    },

    alimenterSelectCategories() {
        const selectModal = document.getElementById("select-modal-categorie");
        const selectFiltre = document.getElementById("filtre-categorie-vehicule");

        if (selectModal) {
            selectModal.innerHTML = `<option value="">Sélectionner une catégorie</option>` +
                this.donnees.categories.map(c => `<option value="${c.id}">${this.echapperHtml(c.nom)} (${c.tarifBase} DH/j)</option>`).join("");
        }
        if (selectFiltre) {
            selectFiltre.innerHTML = `<option value="">Toutes les catégories</option>` +
                this.donnees.categories.map(c => `<option value="${c.id}">${this.echapperHtml(c.nom)}</option>`).join("");
        }
    },

    alimenterSelectVehiculesDisponibles() {
        const select = document.getElementById("select-modal-vehicule");
        if (!select) return;

        const disponibles = this.donnees.vehicules.filter(v => v.statut === "Disponible");
        select.innerHTML = `<option value="">Sélectionner un véhicule disponible</option>` +
            disponibles.map(v => `<option value="${v.id}" data-prix="${v.prixJour}">${this.echapperHtml(v.marque)} ${this.echapperHtml(v.modele)} (${this.echapperHtml(v.immatriculation)}) - ${v.prixJour} DH/j</option>`).join("");
    },

    alimenterSelectLocataires() {
        const select = document.getElementById("select-modal-locataire");
        if (!select) return;

        select.innerHTML = `<option value="">Sélectionner un locataire</option>` +
            this.donnees.locataires.map(l => `<option value="${l.id}">${this.echapperHtml(l.nom)} ${this.echapperHtml(l.prenom)} (${this.echapperHtml(l.cin)})</option>`).join("");
    },

    // ==========================================
    // FILTRES ET RECHERCHES EN TEMPS RÉEL
    // ==========================================

    filtrerVehicules() {
        const q = (document.getElementById("recherche-vehicules").value || "").toLowerCase();
        const cat = document.getElementById("filtre-categorie-vehicule").value;
        const stat = document.getElementById("filtre-statut-vehicule").value;

        const filtres = this.donnees.vehicules.filter(v => {
            const correspondRecherche = !q ||
                v.marque.toLowerCase().includes(q) ||
                v.modele.toLowerCase().includes(q) ||
                v.immatriculation.toLowerCase().includes(q);

            const correspondCat = !cat || v.categorieId == cat;
            const correspondStat = !stat || v.statut === stat;

            return correspondRecherche && correspondCat && correspondStat;
        });

        this.rendreVehicules(filtres);
    },

    filtrerLocataires() {
        const q = (document.getElementById("recherche-locataires").value || "").toLowerCase();
        const filtres = this.donnees.locataires.filter(l => {
            return !q ||
                l.nom.toLowerCase().includes(q) ||
                l.prenom.toLowerCase().includes(q) ||
                l.cin.toLowerCase().includes(q) ||
                (l.permis && l.permis.toLowerCase().includes(q));
        });
        this.rendreLocataires(filtres);
    },

    filtrerContrats() {
        const stat = document.getElementById("filtre-statut-contrat").value;
        const filtres = this.donnees.contrats.filter(c => !stat || c.statut === stat);
        this.rendreContrats(filtres);
    },

    // ==========================================
    // MODALS & UTILITAIRES UI
    // ==========================================

    ouvrirModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove("hidden");
    },

    ouvrirModalVehiculePourCategorie(categorieId) {
        this.ouvrirModal("modal-vehicule");
        const select = document.getElementById("select-modal-categorie");
        if (select) {
            select.value = categorieId;
        }
    },

    fermerModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add("hidden");
    },

    Loading(btn, isLoading) {
        if (!btn) return;
        btn.disabled = isLoading;
        if (isLoading) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Traitement...`;
            btn.classList.add("opacity-75", "cursor-not-allowed");
        } else {
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
            }
            btn.classList.remove("opacity-75", "cursor-not-allowed");
        }
    },

    Toast(message, type = "success") {
        const container = document.getElementById("toast-container");
        if (!container) return;

        const toast = document.createElement("div");
        const styles = {
            success: "bg-emerald-600 text-white shadow-emerald-600/20",
            error: "bg-rose-600 text-white shadow-rose-600/20",
            warning: "bg-amber-600 text-white shadow-amber-600/20"
        };
        const icons = {
            success: '<i class="fa-solid fa-circle-check"></i>',
            error: '<i class="fa-solid fa-circle-xmark"></i>',
            warning: '<i class="fa-solid fa-triangle-exclamation"></i>'
        };

        toast.className = `pointer-events-auto px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 text-sm font-medium transition-all duration-300 transform translate-y-2 opacity-0 ${styles[type] || styles.success}`;
        toast.innerHTML = `${icons[type] || icons.success} <span>${this.echapperHtml(message)}</span>`;

        container.appendChild(toast);

        // Animation d'entrée
        requestAnimationFrame(() => {
            toast.classList.remove("translate-y-2", "opacity-0");
        });

        // Disparition automatique après 3.5 secondes
        setTimeout(() => {
            toast.classList.add("opacity-0", "translate-x-4");
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    },

    genererBadgeStatut(statut) {
        switch (statut) {
            case "Disponible":
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Disponible</span>`;
            case "Loué":
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Loué</span>`;
            case "En maintenance":
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>En maintenance</span>`;
            case "En cours":
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200"><span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>En cours</span>`;
            case "Terminé":
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">Terminé</span>`;
            default:
                return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">${this.echapperHtml(statut)}</span>`;
        }
    },

    echapperHtml(str) {
        if (!str) return "";
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
};

// Initialisation globale
document.addEventListener("DOMContentLoaded", () => app.init());

