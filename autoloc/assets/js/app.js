'use strict';

(() => {
  const API_URL = 'backend/api/api.php';
  const state = {
    stats: null,
    categories: [],
    vehicles: [],
    tenants: [],
    contracts: [],
    modalSubmit: null,
    modalEntity: null,
    busy: false
  };

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    })[char]);
  }

  function safeImage(value) {
    try {
      const url = new URL(String(value || ''), window.location.href);
      if (!['http:', 'https:'].includes(url.protocol)) throw new Error('URL image invalide');
      const normalized = url.href.replace(/[()'\"]/g, char => encodeURIComponent(char));
      return escapeHtml(normalized);
    } catch (error) {
      return 'https://placehold.co/640x420/e8eef5/64748b?text=AutoLoc';
    }
  }

  function normalize(value) {
    return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  }

  function money(value) {
    const amount = Number(value || 0);
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount) + ' DH';
  }

  function dateFr(value) {
    if (!value) return '—';
    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('fr-FR').format(date);
  }

  async function api(resource, options = {}) {
    const url = new URL(API_URL, window.location.href);
    url.searchParams.set('resource', resource);
    if (options.id) url.searchParams.set('id', options.id);
    if (options.action) url.searchParams.set('action', options.action);

    const fetchOptions = {
      method: options.method || 'GET',
      headers: { 'Accept': 'application/json' }
    };
    if (options.body !== undefined) {
      fetchOptions.headers['Content-Type'] = 'application/json';
      fetchOptions.body = JSON.stringify(options.body);
    }

    let response;
    try {
      response = await fetch(url, fetchOptions);
    } catch (error) {
      throw new Error('Impossible de contacter le serveur. Vérifiez que PHP est démarré sur localhost:8000.');
    }
    let result;
    try {
      result = await response.json();
    } catch (error) {
      throw new Error('La réponse du serveur n’est pas un JSON valide. Vérifiez les erreurs PHP.');
    }
    if (!response.ok || !result.success) {
      throw new Error(result.message || `Erreur HTTP ${response.status}`);
    }
    return result;
  }

  function toast(message, type = 'success') {
    const container = $('#toastContainer');
    const item = document.createElement('div');
    item.className = `toast ${type}`;
    item.innerHTML = `<span class="text-lg">${type === 'success' ? '✓' : '!'}</span><span class="text-sm font-semibold"></span><button class="ml-auto opacity-75 hover:opacity-100" aria-label="Fermer">×</button>`;
    $('.text-sm', item).textContent = message;
    $('button', item).addEventListener('click', () => item.remove());
    container.appendChild(item);
    window.setTimeout(() => item.remove(), 4300);
  }

  async function loadAll(showError = true) {
    try {
      const [stats, categories, vehicles, tenants, contracts] = await Promise.all([
        api('stats'), api('categories'), api('vehicules'), api('locataires'), api('contrats')
      ]);
      state.stats = stats.data;
      state.categories = categories.data || [];
      state.vehicles = vehicles.data || [];
      state.tenants = tenants.data || [];
      state.contracts = contracts.data || [];
      renderAll();
    } catch (error) {
      if (showError) toast(error.message, 'error');
      console.error(error);
    }
  }

  function renderAll() {
    renderStats();
    renderRecentContracts();
    renderCategories();
    populateCategoryFilter();
    renderVehicles();
    renderTenants();
    renderContracts();
  }

  function renderStats() {
    const stats = state.stats || {};
    const cards = [
      { label: 'Flotte totale', value: stats.flotteTotale ?? 0, icon: '🚘', hint: 'Véhicules enregistrés', color: 'bg-blue-50 text-blue-600' },
      { label: 'Véhicules disponibles', value: stats.vehiculesDisponibles ?? 0, icon: '✓', hint: 'Prêts à partir', color: 'bg-emerald-50 text-emerald-600' },
      { label: 'Locations en cours', value: stats.locationsEnCours ?? 0, icon: '▤', hint: 'Contrats actifs', color: 'bg-amber-50 text-amber-600' },
      { label: 'Chiffre d’affaires', value: money(stats.chiffreAffaires), icon: '↗', hint: 'Montant cumulé des contrats', color: 'bg-violet-50 text-violet-600' }
    ];
    $('#statsGrid').innerHTML = cards.map(card => `
      <article class="bg-white p-5 rounded-2xl shadow-card border border-slate-50">
        <div class="flex justify-between items-start"><div><p class="text-xs font-bold text-slate-400">${card.label}</p><div class="text-2xl font-black text-ink mt-3">${card.value}</div></div><div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl ${card.color}">${card.icon}</div></div>
        <p class="text-[11px] text-slate-400 mt-4">${card.hint}</p>
      </article>`).join('');
  }

  function statusBadge(status) {
    const classes = status === 'Disponible' ? 'badge-available' : status === 'Loué' ? 'badge-rented' : status === 'En maintenance' ? 'badge-maintenance' : 'badge-done';
    const dot = status === 'Disponible' ? '●' : status === 'Loué' ? '●' : status === 'En maintenance' ? '●' : '●';
    return `<span class="badge ${classes}"><span class="text-[8px]">${dot}</span>${escapeHtml(status)}</span>`;
  }

  function contractBadge(status) {
    return `<span class="badge ${status === 'En cours' ? 'badge-rented' : 'badge-done'}">${status === 'En cours' ? '●' : '✓'} ${escapeHtml(status)}</span>`;
  }

  function getTenant(id) { return state.tenants.find(item => Number(item.id) === Number(id)); }
  function getVehicle(id) { return state.vehicles.find(item => Number(item.id) === Number(id)); }
  function tenantName(id) {
    const tenant = getTenant(id);
    return tenant ? `${tenant.prenom} ${tenant.nom}` : 'Locataire inconnu';
  }
  function vehicleName(id) {
    const vehicle = getVehicle(id);
    return vehicle ? `${vehicle.marque} ${vehicle.modele}` : 'Véhicule inconnu';
  }

  function renderRecentContracts() {
    const contracts = [...state.contracts].sort((a, b) => Number(b.id) - Number(a.id)).slice(0, 5);
    if (!contracts.length) {
      $('#recentContracts').innerHTML = '<div class="empty-state"><div class="text-3xl mb-2">▤</div><p>Aucune location enregistrée.</p></div>';
      return;
    }
    $('#recentContracts').innerHTML = `<table><thead><tr><th>Client</th><th>Véhicule</th><th>Période</th><th>Montant</th><th>Statut</th></tr></thead><tbody>${contracts.map(contract => `
      <tr><td><div class="font-bold text-ink">${escapeHtml(tenantName(contract.locataireId))}</div><div class="text-[10px] text-slate-400">Contrat #${contract.id}</div></td>
      <td>${escapeHtml(vehicleName(contract.vehiculeId))}</td><td>${dateFr(contract.dateDebut)} → ${dateFr(contract.dateFin)}</td><td class="font-extrabold text-ink">${money(contract.prixTotal)}</td><td>${contractBadge(contract.statut)}</td></tr>`).join('')}</tbody></table>`;
  }

  function renderCategories() {
    if (!state.categories.length) {
      $('#categoriesGrid').innerHTML = '<div class="empty-state bg-white rounded-2xl xl:col-span-2">Aucune catégorie. Ajoutez votre première catégorie.</div>';
      return;
    }
    $('#categoriesGrid').innerHTML = state.categories.map(category => {
      const vehicles = state.vehicles.filter(vehicle => Number(vehicle.categorieId) === Number(category.id));
      return `<article class="bg-white rounded-2xl shadow-card overflow-hidden border border-slate-100">
        <div class="category-banner px-5 py-5 flex items-end" style="background-image:url('${safeImage(category.image)}')">
          <div class="category-banner-content flex justify-between items-end gap-3 w-full"><div><div class="text-[10px] text-white/75 uppercase tracking-[.16em] font-bold">Catégorie</div><h3 class="text-2xl font-black mt-1">${escapeHtml(category.nom)}</h3><p class="text-xs text-white/80 mt-1 max-w-sm">${escapeHtml(category.description)}</p></div><div class="text-right shrink-0"><div class="text-[10px] text-white/70">À partir de</div><div class="font-black text-lg">${money(category.tarifBase)}</div><div class="text-[10px] text-white/70">/ jour</div></div></div>
        </div>
        <div class="px-5 py-4"><div class="flex items-center justify-between mb-3"><div class="text-xs font-extrabold text-slate-500">${vehicles.length} véhicule${vehicles.length > 1 ? 's' : ''} dans cette catégorie</div><div class="flex gap-1"><button class="btn-ghost" data-edit-category="${category.id}">Modifier</button><button class="btn-danger" data-delete-category="${category.id}">Supprimer</button></div></div>
          ${vehicles.length ? `<div class="space-y-3">${vehicles.map(vehicle => `<div class="flex items-center gap-3 border border-slate-100 rounded-xl p-2.5"><img class="w-[74px] h-[54px] rounded-lg object-cover bg-slate-100" src="${safeImage(vehicle.image)}" alt="${escapeHtml(vehicle.marque)} ${escapeHtml(vehicle.modele)}" loading="lazy"><div class="min-w-0 flex-1"><div class="text-sm font-extrabold text-ink truncate">${escapeHtml(vehicle.marque)} ${escapeHtml(vehicle.modele)}</div><div class="text-[10px] text-slate-400 mt-1">${escapeHtml(vehicle.immatriculation)} · ${money(vehicle.prixJour)}/jour</div></div><div>${statusBadge(vehicle.statut)}</div><button class="btn-ghost" data-edit-vehicle="${vehicle.id}">Voir</button></div>`).join('')}</div>` : '<div class="text-xs text-slate-400 py-4 text-center">Aucun véhicule rattaché à cette catégorie.</div>'}
          <button class="w-full mt-4 py-2.5 rounded-xl border border-dashed border-teal/50 text-teal text-xs font-extrabold hover:bg-teal/5" data-add-vehicle-category="${category.id}">＋ Ajouter un véhicule dans cette catégorie</button>
        </div>
      </article>`;
    }).join('');
  }

  function populateCategoryFilter() {
    const select = $('#vehicleCategoryFilter');
    if (!select) return;
    const current = select.value;
    select.innerHTML = '<option value="">Toutes les catégories</option>' + state.categories.map(category => `<option value="${category.id}">${escapeHtml(category.nom)}</option>`).join('');
    select.value = current;
  }

  function renderVehicles() {
    const search = normalize($('#vehicleSearch')?.value || '');
    const categoryId = $('#vehicleCategoryFilter')?.value || '';
    const status = $('#vehicleStatusFilter')?.value || '';
    const rows = state.vehicles.filter(vehicle => {
      const haystack = normalize([vehicle.marque, vehicle.modele, vehicle.immatriculation, vehicle.categorieNom].join(' '));
      return (!search || haystack.includes(search)) && (!categoryId || String(vehicle.categorieId) === categoryId) && (!status || vehicle.statut === status);
    });
    $('#vehicleCount').textContent = `${rows.length} véhicule${rows.length > 1 ? 's' : ''}`;
    if (!rows.length) {
      $('#vehiclesTable').innerHTML = '<div class="empty-state"><div class="text-3xl mb-2">🚘</div><p>Aucun véhicule ne correspond à votre recherche.</p></div>';
      return;
    }
    $('#vehiclesTable').innerHTML = `<table><thead><tr><th>Véhicule</th><th>Immatriculation</th><th>Catégorie</th><th>Prix / jour</th><th>Statut</th><th>Actions</th></tr></thead><tbody>${rows.map(vehicle => `
      <tr><td><div class="flex items-center gap-3"><img src="${safeImage(vehicle.image)}" class="vehicle-photo" alt="${escapeHtml(vehicle.modele)}" loading="lazy"><div><div class="font-extrabold text-ink">${escapeHtml(vehicle.marque)} ${escapeHtml(vehicle.modele)}</div><div class="text-[10px] text-slate-400">ID #${vehicle.id}</div></div></div></td>
      <td class="font-semibold">${escapeHtml(vehicle.immatriculation)}</td><td>${escapeHtml(vehicle.categorieNom || '—')}</td><td class="font-extrabold text-ink">${money(vehicle.prixJour)}</td><td>${statusBadge(vehicle.statut)}</td><td><div class="flex gap-1"><button class="btn-ghost" data-edit-vehicle="${vehicle.id}">Modifier</button><button class="btn-danger" data-delete-vehicle="${vehicle.id}">Supprimer</button></div></td></tr>`).join('')}</tbody></table>`;
  }

  function renderTenants() {
    const search = normalize($('#tenantSearch')?.value || '');
    const rows = state.tenants.filter(tenant => normalize([tenant.nom, tenant.prenom, tenant.cin, tenant.telephone, tenant.email].join(' ')).includes(search));
    if (!rows.length) {
      $('#tenantsTable').innerHTML = '<div class="empty-state"><div class="text-3xl mb-2">♙</div><p>Aucun locataire trouvé.</p></div>';
      return;
    }
    $('#tenantsTable').innerHTML = `<table><thead><tr><th>Locataire</th><th>CIN</th><th>Téléphone</th><th>E-mail</th><th>Permis</th><th>Actions</th></tr></thead><tbody>${rows.map(tenant => `
      <tr><td><div class="flex items-center gap-3"><div class="w-9 h-9 rounded-full bg-teal/10 text-teal font-black flex items-center justify-center">${escapeHtml((tenant.prenom[0] || '') + (tenant.nom[0] || ''))}</div><div><div class="font-extrabold text-ink">${escapeHtml(tenant.prenom)} ${escapeHtml(tenant.nom)}</div><div class="text-[10px] text-slate-400">Client #${tenant.id}</div></div></div></td><td class="font-bold">${escapeHtml(tenant.cin)}</td><td>${escapeHtml(tenant.telephone)}</td><td>${escapeHtml(tenant.email)}</td><td>${escapeHtml(tenant.permis)}</td><td><div class="flex gap-1"><button class="btn-ghost" data-edit-tenant="${tenant.id}">Modifier</button><button class="btn-danger" data-delete-tenant="${tenant.id}">Supprimer</button></div></td></tr>`).join('')}</tbody></table>`;
  }

  function renderContracts() {
    const search = normalize($('#contractSearch')?.value || '');
    const status = $('#contractStatusFilter')?.value || '';
    const rows = [...state.contracts].filter(contract => {
      const haystack = normalize([tenantName(contract.locataireId), getTenant(contract.locataireId)?.cin, vehicleName(contract.vehiculeId), getVehicle(contract.vehiculeId)?.immatriculation].join(' '));
      return (!search || haystack.includes(search)) && (!status || contract.statut === status);
    }).sort((a, b) => Number(b.id) - Number(a.id));
    if (!rows.length) {
      $('#contractsTable').innerHTML = '<div class="empty-state"><div class="text-3xl mb-2">▤</div><p>Aucun contrat ne correspond à votre recherche.</p></div>';
      return;
    }
    $('#contractsTable').innerHTML = `<table><thead><tr><th>Contrat</th><th>Locataire</th><th>Véhicule</th><th>Période</th><th>Durée</th><th>Total</th><th>Statut</th><th>Actions</th></tr></thead><tbody>${rows.map(contract => `
      <tr><td class="font-extrabold text-ink">#${contract.id}</td><td><div class="font-bold text-ink">${escapeHtml(tenantName(contract.locataireId))}</div><div class="text-[10px] text-slate-400">${escapeHtml(getTenant(contract.locataireId)?.cin || '—')}</div></td><td><div class="font-bold text-ink">${escapeHtml(vehicleName(contract.vehiculeId))}</div><div class="text-[10px] text-slate-400">${escapeHtml(getVehicle(contract.vehiculeId)?.immatriculation || '—')}</div></td><td>${dateFr(contract.dateDebut)} → ${dateFr(contract.dateFin)}</td><td>${Number(contract.dureeJours || daysBetween(contract.dateDebut, contract.dateFin))} jours</td><td class="font-extrabold text-ink">${money(contract.prixTotal)}</td><td>${contractBadge(contract.statut)}</td><td><div class="flex gap-1">${contract.statut === 'En cours' ? `<button class="btn-ghost" data-edit-contract="${contract.id}">Modifier</button><button class="btn-primary !px-2.5 !py-2 text-[11px]" data-close-contract="${contract.id}">Clôturer</button>` : `<button class="btn-danger" data-delete-contract="${contract.id}">Supprimer</button>`}</div></td></tr>`).join('')}</tbody></table>`;
  }

  function daysBetween(start, end) {
    if (!start || !end) return 0;
    const first = new Date(`${start}T00:00:00Z`);
    const last = new Date(`${end}T00:00:00Z`);
    const days = Math.round((last - first) / 86400000);
    return days > 0 ? days : 0;
  }

  function openModal(title, content, submitHandler, wide = false) {
    $('#modalTitle').textContent = title;
    $('#modalContent').innerHTML = content;
    $('#modalCard').classList.toggle('wide', wide);
    $('#modalBackdrop').classList.add('open');
    document.body.classList.add('overflow-hidden');
    state.modalSubmit = submitHandler;
    state.busy = false;
  }

  function closeModal() {
    $('#modalBackdrop').classList.remove('open');
    document.body.classList.remove('overflow-hidden');
    $('#modalForm').reset();
    state.modalSubmit = null;
    state.busy = false;
  }

  function inputField(name, label, value = '', type = 'text', required = true, placeholder = '') {
    return `<div><label class="field-label" for="f-${name}">${label}${required ? ' *' : ''}</label><input class="field" id="f-${name}" name="${name}" type="${type}" value="${escapeHtml(value)}" placeholder="${escapeHtml(placeholder)}" ${required ? 'required' : ''} ${type === 'number' ? 'min="0" step="0.01"' : ''}></div>`;
  }

  function selectField(name, label, options, selected = '', required = true) {
    return `<div><label class="field-label" for="f-${name}">${label}${required ? ' *' : ''}</label><select class="field" id="f-${name}" name="${name}" ${required ? 'required' : ''}>${options.map(option => `<option value="${escapeHtml(option.value)}" ${String(option.value) === String(selected) ? 'selected' : ''} ${option.disabled ? 'disabled' : ''}>${escapeHtml(option.label)}</option>`).join('')}</select></div>`;
  }

  function textAreaField(name, label, value = '') {
    return `<div class="md:col-span-2"><label class="field-label" for="f-${name}">${label} *</label><textarea class="field min-h-[90px]" id="f-${name}" name="${name}" required>${escapeHtml(value)}</textarea></div>`;
  }

  function formValues(form) {
    return Object.fromEntries(new FormData(form).entries());
  }

  function openCategoryModal(category = null) {
    const isEdit = Boolean(category);
    const content = `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      ${inputField('nom', 'Nom de la catégorie', category?.nom || '', 'text', true, 'Ex. Économique')}
      ${inputField('tarifBase', 'Tarif indicatif / jour (DH)', category?.tarifBase ?? '', 'number')}
      ${inputField('image', 'URL de la bannière', category?.image || '', 'url', true, 'https://...')}
      ${textAreaField('description', 'Description', category?.description || '')}
    </div>`;
    openModal(isEdit ? 'Modifier la catégorie' : 'Ajouter une catégorie', content, async form => {
      const payload = formValues(form);
      payload.tarifBase = Number(payload.tarifBase);
      await api('categories', { method: isEdit ? 'PUT' : 'POST', id: category?.id, body: payload });
      closeModal(); await loadAll(false); toast(isEdit ? 'Catégorie modifiée.' : 'Catégorie ajoutée.');
    });
  }

  function openVehicleModal(vehicle = null, presetCategoryId = '') {
    const isEdit = Boolean(vehicle);
    if (!state.categories.length) { toast('Ajoutez d’abord une catégorie.', 'error'); return; }
    const categoryOptions = [{ value: '', label: 'Choisir une catégorie', disabled: true }, ...state.categories.map(category => ({ value: category.id, label: category.nom }))];
    const statusOptions = ['Disponible', 'En maintenance', 'Loué'].map(value => ({ value, label: value }));
    const content = `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      ${inputField('immatriculation', 'Immatriculation', vehicle?.immatriculation || '', 'text', true, 'Ex. 12345-A-6')}
      ${inputField('marque', 'Marque', vehicle?.marque || '', 'text', true, 'Ex. Dacia')}
      ${inputField('modele', 'Modèle', vehicle?.modele || '', 'text', true, 'Ex. Sandero')}
      ${selectField('categorieId', 'Catégorie', categoryOptions, vehicle?.categorieId || presetCategoryId)}
      ${inputField('prixJour', 'Prix par jour (DH)', vehicle?.prixJour ?? '', 'number')}
      ${selectField('statut', 'Statut', statusOptions, vehicle?.statut || 'Disponible')}
      <div class="md:col-span-2">${inputField('image', 'URL de la photo', vehicle?.image || '', 'url', true, 'https://...')}</div>
    </div>`;
    openModal(isEdit ? 'Modifier le véhicule' : 'Ajouter un véhicule', content, async form => {
      const payload = formValues(form);
      payload.categorieId = Number(payload.categorieId);
      payload.prixJour = Number(payload.prixJour);
      await api('vehicules', { method: isEdit ? 'PUT' : 'POST', id: vehicle?.id, body: payload });
      closeModal(); await loadAll(false); toast(isEdit ? 'Véhicule modifié.' : 'Véhicule ajouté.');
    });
  }

  function openTenantModal(tenant = null) {
    const isEdit = Boolean(tenant);
    const content = `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      ${inputField('cin', 'CIN', tenant?.cin || '', 'text', true, 'Ex. AB123456')}
      ${inputField('permis', 'Numéro de permis', tenant?.permis || '', 'text')}
      ${inputField('prenom', 'Prénom', tenant?.prenom || '')}
      ${inputField('nom', 'Nom', tenant?.nom || '')}
      ${inputField('telephone', 'Téléphone', tenant?.telephone || '','tel',true,'+212 6...')}
      ${inputField('email', 'Adresse e-mail', tenant?.email || '', 'email')}
    </div>`;
    openModal(isEdit ? 'Modifier le locataire' : 'Ajouter un locataire', content, async form => {
      const payload = formValues(form);
      await api('locataires', { method: isEdit ? 'PUT' : 'POST', id: tenant?.id, body: payload });
      closeModal(); await loadAll(false); toast(isEdit ? 'Locataire modifié.' : 'Locataire ajouté.');
    });
  }

  function openContractModal(contract = null) {
    const isEdit = Boolean(contract);
    if (!state.tenants.length) { toast('Ajoutez d’abord un locataire.', 'error'); return; }
    const availableVehicles = state.vehicles.filter(vehicle => vehicle.statut === 'Disponible' || (isEdit && Number(vehicle.id) === Number(contract.vehiculeId)));
    if (!availableVehicles.length) { toast('Aucun véhicule disponible pour une nouvelle réservation.', 'error'); return; }
    const tenantOptions = [{ value: '', label: 'Choisir un locataire', disabled: true }, ...state.tenants.map(tenant => ({ value: tenant.id, label: `${tenant.prenom} ${tenant.nom} · ${tenant.cin}` }))];
    const vehicleOptions = [{ value: '', label: 'Choisir un véhicule', disabled: true }, ...availableVehicles.map(vehicle => ({ value: vehicle.id, label: `${vehicle.marque} ${vehicle.modele} · ${vehicle.immatriculation} · ${money(vehicle.prixJour)}/j` }))];
    const content = `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="md:col-span-2">${selectField('locataireId', 'Locataire', tenantOptions, contract?.locataireId || '')}</div>
      <div class="md:col-span-2">${selectField('vehiculeId', 'Véhicule disponible', vehicleOptions, contract?.vehiculeId || '')}</div>
      ${inputField('dateDebut', 'Date de début', contract?.dateDebut || new Date().toISOString().slice(0,10), 'date')}
      ${inputField('dateFin', 'Date de fin', contract?.dateFin || '', 'date')}
      <div class="md:col-span-2 bg-[#f2fbf9] border border-teal/15 rounded-2xl p-4 mt-1">
        <div class="flex items-center justify-between mb-3"><div><div class="text-[10px] uppercase tracking-[.15em] text-teal font-extrabold">Estimation automatique</div><div class="text-xs text-slate-500 mt-1">Le montant est recalculé selon le véhicule et les dates.</div></div><div class="text-2xl">▧</div></div>
        <div class="grid grid-cols-3 gap-3"><div><div class="text-[10px] text-slate-400">Durée</div><div id="quoteDays" class="font-black text-ink mt-1">— jours</div></div><div><div class="text-[10px] text-slate-400">Prix / jour</div><div id="quoteRate" class="font-black text-ink mt-1">—</div></div><div><div class="text-[10px] text-slate-400">Total estimé</div><div id="quoteAmount" class="font-black text-teal mt-1">—</div></div></div>
      </div>
    </div>`;
    openModal(isEdit ? `Modifier le contrat #${contract.id}` : 'Nouvelle réservation', content, async form => {
      const payload = formValues(form);
      payload.locataireId = Number(payload.locataireId);
      payload.vehiculeId = Number(payload.vehiculeId);
      await api('contrats', { method: isEdit ? 'PUT' : 'POST', id: contract?.id, body: payload });
      closeModal(); await loadAll(false); toast(isEdit ? 'Contrat modifié.' : 'Réservation créée.');
    }, true);
    const recalculate = () => {
      const vehicle = getVehicle(Number($('#f-vehiculeId')?.value));
      const days = daysBetween($('#f-dateDebut')?.value, $('#f-dateFin')?.value);
      $('#quoteDays').textContent = days ? `${days} jour${days > 1 ? 's' : ''}` : '— jours';
      $('#quoteRate').textContent = vehicle ? money(vehicle.prixJour) : '—';
      $('#quoteAmount').textContent = vehicle && days ? money(vehicle.prixJour * days) : '—';
    };
    ['#f-vehiculeId', '#f-dateDebut', '#f-dateFin'].forEach(selector => $(selector)?.addEventListener('change', recalculate));
    recalculate();
  }

  async function submitModal(event) {
    event.preventDefault();
    if (state.busy || typeof state.modalSubmit !== 'function') return;
    const form = $('#modalForm');
    if (!form.reportValidity()) return;
    const button = $('#modalSubmit');
    const oldText = button.textContent;
    state.busy = true;
    button.disabled = true;
    button.textContent = 'Enregistrement…';
    button.classList.add('opacity-60');
    try {
      await state.modalSubmit(form);
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      state.busy = false;
      button.disabled = false;
      button.textContent = oldText;
      button.classList.remove('opacity-60');
    }
  }

  async function removeEntity(resource, id, label) {
    if (!window.confirm(`Voulez-vous vraiment supprimer ${label} ?`)) return;
    try {
      const result = await api(resource, { method: 'DELETE', id });
      await loadAll(false);
      toast(result.message || 'Élément supprimé.');
    } catch (error) { toast(error.message, 'error'); }
  }

  async function closeContract(id) {
    if (!window.confirm('Clôturer ce contrat et rendre le véhicule disponible ?')) return;
    try {
      const result = await api('contrats', { method: 'POST', id, action: 'cloturer' });
      await loadAll(false);
      toast(result.message || 'Contrat clôturé.');
    } catch (error) { toast(error.message, 'error'); }
  }

  function navigate(section) {
    const titles = { dashboard: 'Vue d’ensemble', categories: 'Catégories', vehicules: 'Véhicules', locataires: 'Locataires', contrats: 'Contrats' };
    $$('.section-panel').forEach(panel => panel.classList.toggle('active', panel.id === `section-${section}`));
    $$('.nav-link').forEach(link => link.classList.toggle('active', link.dataset.section === section));
    $('#pageTitle').textContent = titles[section] || 'AutoLoc';
    toggleSidebar(false);
  }

  function toggleSidebar(force) {
    const sidebar = $('#sidebar');
    const shouldOpen = typeof force === 'boolean' ? force : !sidebar.classList.contains('open');
    sidebar.classList.toggle('open', shouldOpen);
    $('#mobileShade').classList.toggle('open', shouldOpen);
    $('#mobileShade').classList.toggle('hidden', !shouldOpen);
  }

  document.addEventListener('click', event => {
    const target = event.target.closest('[data-section], [data-edit-category], [data-delete-category], [data-add-vehicle-category], [data-edit-vehicle], [data-delete-vehicle], [data-edit-tenant], [data-delete-tenant], [data-edit-contract], [data-close-contract], [data-delete-contract]');
    if (!target) return;
    if (target.dataset.section) navigate(target.dataset.section);
    if (target.dataset.editCategory) openCategoryModal(state.categories.find(x => Number(x.id) === Number(target.dataset.editCategory)));
    if (target.dataset.deleteCategory) removeEntity('categories', target.dataset.deleteCategory, 'cette catégorie');
    if (target.dataset.addVehicleCategory) openVehicleModal(null, target.dataset.addVehicleCategory);
    if (target.dataset.editVehicle) openVehicleModal(state.vehicles.find(x => Number(x.id) === Number(target.dataset.editVehicle)));
    if (target.dataset.deleteVehicle) removeEntity('vehicules', target.dataset.deleteVehicle, 'ce véhicule');
    if (target.dataset.editTenant) openTenantModal(state.tenants.find(x => Number(x.id) === Number(target.dataset.editTenant)));
    if (target.dataset.deleteTenant) removeEntity('locataires', target.dataset.deleteTenant, 'ce locataire');
    if (target.dataset.editContract) openContractModal(state.contracts.find(x => Number(x.id) === Number(target.dataset.editContract)));
    if (target.dataset.closeContract) closeContract(Number(target.dataset.closeContract));
    if (target.dataset.deleteContract) removeEntity('contrats', target.dataset.deleteContract, 'ce contrat terminé');
  });

  $('#modalForm').addEventListener('submit', submitModal);
  $('#modalBackdrop').addEventListener('click', event => { if (event.target === $('#modalBackdrop')) closeModal(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') closeModal(); });
  $('#vehicleSearch').addEventListener('input', renderVehicles);
  $('#vehicleCategoryFilter').addEventListener('change', renderVehicles);
  $('#vehicleStatusFilter').addEventListener('change', renderVehicles);
  $('#tenantSearch').addEventListener('input', renderTenants);
  $('#contractSearch').addEventListener('input', renderContracts);
  $('#contractStatusFilter').addEventListener('change', renderContracts);

  window.AutoLoc = {
    navigate,
    toggleSidebar,
    closeModal,
    openContractModal,
    openVehicleModal,
    openTenantModal,
    openCategoryModal,
    refresh: () => loadAll()
  };

  loadAll();
})();
