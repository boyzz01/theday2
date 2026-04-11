import { reactive, ref } from 'vue';
import axios from 'axios';

// ─── axios instance with CSRF cookie ─────────────────────────────────────────
// Sanctum session-cookie auth: include credentials so Laravel reads the session.
axios.defaults.withCredentials    = true;
axios.defaults.withXSRFToken      = true;

// ─── Default state factories ──────────────────────────────────────────────────

function defaultDetails() {
    return {
        groom_name:           '',
        bride_name:           '',
        groom_parent_names:   '',
        bride_parent_names:   '',
        groom_photo_url:      '',
        bride_photo_url:      '',
        opening_text:         '',
        closing_text:         '',
        cover_photo_url:      '',
    };
}

function defaultEvent() {
    return {
        _key:          Date.now() + Math.random(),
        event_name:    '',
        event_date:    '',
        start_time:    '',
        end_time:      '',
        venue_name:    '',
        venue_address: '',
        maps_url:      '',
        sort_order:    0,
    };
}

function defaultConfig() {
    return {
        primary_color: '#D4A373',
        font:          'Playfair Display',
    };
}

// ─── Composable ───────────────────────────────────────────────────────────────

export function useInvitationEditor(template, invitation = null) {

    // ── Core identifiers ─────────────────────────────────────────
    const invitationId  = ref(invitation?.id ?? null);
    const currentStep   = ref(1);
    const isSaving      = ref(false);
    const saveError     = ref(null);
    const lastSavedStep = ref(0);

    // ── Step 1 — Basic info ──────────────────────────────────────
    const basic = reactive({
        title:      invitation?.title ?? '',
        event_type: invitation?.event_type ?? 'pernikahan',
    });

    const details = reactive({
        ...defaultDetails(),
        ...(invitation?.details ?? {}),
    });

    // ── Step 2 — Events ──────────────────────────────────────────
    const events = ref(invitation?.events?.length
        ? invitation.events.map((e, i) => ({
            _key:          Date.now() + Math.random() + i,
            _serverId:     e.id,
            event_name:    e.event_name    ?? '',
            event_date:    e.event_date    ?? '',
            start_time:    e.start_time    ?? '',
            end_time:      e.end_time      ?? '',
            venue_name:    e.venue_name    ?? '',
            venue_address: e.venue_address ?? '',
            maps_url:      e.maps_url      ?? '',
            sort_order:    e.sort_order    ?? i,
        }))
        : [defaultEvent()]
    );

    // ── Step 3 — Gallery ─────────────────────────────────────────
    const galleries = ref(invitation?.galleries?.length
        ? invitation.galleries.map((g, i) => ({
            _key:      Date.now() + Math.random() + i,
            _serverId: g.id,
            image_url: g.image_url,
            caption:   g.caption   ?? '',
        }))
        : []
    );

    // ── Step 4 — Music ───────────────────────────────────────────
    const selectedMusic = ref(invitation?.music?.[0]
        ? { title: invitation.music[0].title, file_url: invitation.music[0].file_url }
        : null
    );

    // ── Step 5 — Config ──────────────────────────────────────────
    const customConfig = reactive({
        ...defaultConfig(),
        ...(template?.default_config ?? {}),
        ...(invitation?.custom_config ?? {}),
    });

    // ── Step 6 — Publish ─────────────────────────────────────────
    const publish = reactive({
        slug:                  invitation?.slug                  ?? '',
        is_password_protected: invitation?.is_password_protected ?? false,
        password:              '',
        expires_at:            invitation?.expires_at            ?? '',
    });

    // ── Restore step from DB ──────────────────────────────────────
    if (invitation?.id) {
        const saved = invitation.current_step ?? 0;
        lastSavedStep.value = saved;
        currentStep.value   = saved > 0 ? Math.min(saved + 1, 6) : 1;
    }

    // ── API helpers ───────────────────────────────────────────────

    function apiUrl(path) {
        return `/api${path}`;
    }

    async function apiCall(fn) {
        isSaving.value  = true;
        saveError.value = null;
        try {
            return await fn();
        } catch (e) {
            const msg = Object.values(e.response?.data?.errors ?? {})[0]?.[0]
                ?? e.response?.data?.message
                ?? 'Terjadi kesalahan. Coba lagi.';
            saveError.value = msg;
            throw e;
        } finally {
            isSaving.value = false;
        }
    }

    // ── Pending photo queue (deferred until saveStep1) ────────────
    // key = field name without _url (e.g. "groom_photo"), value = File
    const pendingPhotoFiles = reactive({});

    // ── Upload helpers ────────────────────────────────────────────

    async function uploadFile(file, type = 'photo') {
        if (!invitationId.value) throw new Error('Simpan informasi dasar terlebih dahulu.');

        const form = new FormData();
        form.append('image', file);
        if (type === 'cover') form.append('type', 'cover');

        // Use the details endpoint for single-photo uploads;
        // gallery uploads use the dedicated gallery endpoint.
        if (type === 'gallery') {
            const res = await axios.post(
                apiUrl(`/invitations/${invitationId.value}/galleries`),
                form,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            );
            return res.data.data.image_url;
        }

        // For profile/cover photos, PATCH details with the file
        const res = await axios.post(
            apiUrl(`/invitations/${invitationId.value}/details`),
            form,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        return res.data.data;
    }

    // Queue the file locally and show a preview — actual upload happens in saveStep1
    function uploadPhotoField(file, field) {
        // Revoke previous object URL to avoid memory leak
        if (pendingPhotoFiles[field] && details[`${field}_url`]?.startsWith('blob:')) {
            URL.revokeObjectURL(details[`${field}_url`]);
        }
        pendingPhotoFiles[field] = file;
        details[`${field}_url`] = URL.createObjectURL(file);
    }

    async function uploadAudio(file) {
        if (!invitationId.value) throw new Error('Simpan informasi dasar terlebih dahulu.');

        const form = new FormData();
        form.append('type', 'upload');
        form.append('file', file);

        const res = await axios.post(
            apiUrl(`/invitations/${invitationId.value}/music`),
            form,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        return { url: res.data.data.file_url, name: res.data.data.title };
    }

    // ── Save methods (one per step) ───────────────────────────────

    async function saveStep1() {
        return apiCall(async () => {
            if (!invitationId.value) {
                // Create
                const res = await axios.post(apiUrl('/invitations'), {
                    template_id: template.id,
                    title:       basic.title,
                    event_type:  basic.event_type,
                });
                invitationId.value = res.data.data.id;
                publish.slug       = res.data.data.slug;
                // Update URL so refresh lands on the edit page with correct data
                window.history.replaceState(
                    null, '',
                    `/dashboard/invitations/${invitationId.value}/edit`
                );
                await axios.put(apiUrl(`/invitations/${invitationId.value}`), { current_step: 1 });
            } else {
                // Update basic fields
                await axios.put(apiUrl(`/invitations/${invitationId.value}`), {
                    title:        basic.title,
                    event_type:   basic.event_type,
                    current_step: Math.max(lastSavedStep.value, 1),
                });
            }

            // Sync text details
            await axios.post(
                apiUrl(`/invitations/${invitationId.value}/details`),
                sanitizedDetailsPayload()
            );

            // Upload any pending photo files (queued when user picked a file)
            for (const [field, file] of Object.entries(pendingPhotoFiles)) {
                const form = new FormData();
                form.append(field, file);
                const res = await axios.post(
                    apiUrl(`/invitations/${invitationId.value}/details`),
                    form,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                );
                const urlField = `${field}_url`;
                if (res.data.data?.[urlField]) {
                    URL.revokeObjectURL(details[urlField]);
                    details[urlField] = res.data.data[urlField];
                }
                delete pendingPhotoFiles[field];
            }

            lastSavedStep.value = Math.max(lastSavedStep.value, 1);
        });
    }

    async function saveStep2() {
        if (!invitationId.value) return;
        return apiCall(async () => {
            // Sync all events: delete existing then re-create in order
            // We do this by calling storeEvent for each; a cleaner approach is a
            // bulk-sync endpoint, but here we use the individual endpoints.
            // Strategy: track server-side IDs on each event object.
            for (let i = 0; i < events.value.length; i++) {
                const ev = events.value[i];
                const payload = {
                    event_name:    ev.event_name,
                    event_date:    ev.event_date,
                    start_time:    ev.start_time || null,
                    end_time:      ev.end_time   || null,
                    venue_name:    ev.venue_name,
                    venue_address: ev.venue_address || null,
                    maps_url:      ev.maps_url || null,
                    sort_order:    i,
                };

                if (ev._serverId) {
                    await axios.put(
                        apiUrl(`/invitations/${invitationId.value}/events/${ev._serverId}`),
                        payload
                    );
                } else {
                    const res = await axios.post(
                        apiUrl(`/invitations/${invitationId.value}/events`),
                        payload
                    );
                    ev._serverId = res.data.data.id;
                }
            }

            await axios.put(apiUrl(`/invitations/${invitationId.value}`), { current_step: Math.max(lastSavedStep.value, 2) });
            lastSavedStep.value = Math.max(lastSavedStep.value, 2);
        });
    }

    async function saveStep3() {
        if (!invitationId.value) return;
        return apiCall(async () => {
            // Gallery items that already have server IDs are already saved;
            // reorder them if needed.
            const serverIds = galleries.value
                .filter(g => g._serverId)
                .map(g => g._serverId);

            if (serverIds.length > 0) {
                await axios.put(
                    apiUrl(`/invitations/${invitationId.value}/galleries/reorder`),
                    { ids: serverIds }
                );
            }

            await axios.put(apiUrl(`/invitations/${invitationId.value}`), { current_step: Math.max(lastSavedStep.value, 3) });
            lastSavedStep.value = Math.max(lastSavedStep.value, 3);
        });
    }

    async function saveStep4() {
        if (!invitationId.value || !selectedMusic.value) return;
        return apiCall(async () => {
            await axios.post(
                apiUrl(`/invitations/${invitationId.value}/music`),
                {
                    type:     'default',
                    title:    selectedMusic.value.title,
                    file_url: selectedMusic.value.file_url ?? '',
                }
            );
            await axios.put(apiUrl(`/invitations/${invitationId.value}`), { current_step: Math.max(lastSavedStep.value, 4) });
            lastSavedStep.value = Math.max(lastSavedStep.value, 4);
        });
    }

    async function saveStep5() {
        if (!invitationId.value) return;
        return apiCall(async () => {
            await axios.put(apiUrl(`/invitations/${invitationId.value}`), {
                custom_config: { ...customConfig },
                current_step:  Math.max(lastSavedStep.value, 5),
            });
            lastSavedStep.value = Math.max(lastSavedStep.value, 5);
        });
    }

    async function saveStep6(action = 'draft') {
        if (!invitationId.value) return;
        return apiCall(async () => {
            if (action === 'publish') {
                const payload = {
                    slug:                  publish.slug,
                    is_password_protected: publish.is_password_protected,
                    expires_at:            publish.expires_at || null,
                };
                if (publish.is_password_protected && publish.password) {
                    payload.password = publish.password;
                }
                const res = await axios.put(
                    apiUrl(`/invitations/${invitationId.value}/publish`),
                    payload
                );
                lastSavedStep.value = Math.max(lastSavedStep.value, 6);
                return res.data;
            } else {
                // Draft: just update slug/settings without publishing
                await axios.put(apiUrl(`/invitations/${invitationId.value}`), {
                    slug: publish.slug,
                });
                lastSavedStep.value = Math.max(lastSavedStep.value, 6);
                return { status: 'draft' };
            }
        });
    }

    // ── Event helpers ─────────────────────────────────────────────

    function addEvent() {
        events.value.push({ ...defaultEvent(), sort_order: events.value.length });
    }

    async function removeEvent(index) {
        const ev = events.value[index];
        if (ev._serverId && invitationId.value) {
            try {
                await axios.delete(
                    apiUrl(`/invitations/${invitationId.value}/events/${ev._serverId}`)
                );
            } catch {
                // best-effort: remove locally regardless
            }
        }
        events.value.splice(index, 1);
    }

    function moveEvent(from, to) {
        const arr  = events.value;
        const item = arr.splice(from, 1)[0];
        arr.splice(to, 0, item);
        arr.forEach((e, i) => (e.sort_order = i));
    }

    // ── Gallery helpers ───────────────────────────────────────────

    function addGalleryItem(url, caption = '', serverId = null) {
        galleries.value.push({
            _key:       Date.now() + Math.random(),
            _serverId:  serverId,
            image_url:  url,
            caption:    caption,
            sort_order: galleries.value.length,
        });
    }

    async function removeGallery(index) {
        const item = galleries.value[index];
        if (item._serverId && invitationId.value) {
            try {
                await axios.delete(
                    apiUrl(`/invitations/${invitationId.value}/galleries/${item._serverId}`)
                );
            } catch {
                // best-effort
            }
        }
        galleries.value.splice(index, 1);
    }

    function moveGallery(from, to) {
        const arr  = galleries.value;
        const item = arr.splice(from, 1)[0];
        arr.splice(to, 0, item);
        arr.forEach((g, i) => (g.sort_order = i));
    }

    // Upload a gallery file immediately and add to the list
    async function uploadGalleryFile(file) {
        if (!invitationId.value) throw new Error('Simpan informasi dasar terlebih dahulu.');

        const form = new FormData();
        form.append('image', file);

        const res = await axios.post(
            apiUrl(`/invitations/${invitationId.value}/galleries`),
            form,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        const data = res.data.data;
        galleries.value.push({
            _key:       Date.now() + Math.random(),
            _serverId:  data.id,
            image_url:  data.image_url,
            caption:    data.caption ?? '',
            sort_order: data.sort_order,
        });

        return data.image_url;
    }

    // ── Draft restore (from sessionStorage) ──────────────────────

    function restoreFromDraft(draft) {
        if (!draft) return;
        if (draft.basic)       Object.assign(basic, draft.basic);
        if (draft.details)     Object.assign(details, draft.details);
        if (draft.events?.length) events.value = draft.events;
        if (draft.customConfig) Object.assign(customConfig, draft.customConfig);
    }

    // ── Internal helpers ──────────────────────────────────────────

    function sanitizedDetailsPayload() {
        return {
            groom_name:         details.groom_name         || null,
            bride_name:         details.bride_name         || null,
            groom_parent_names: details.groom_parent_names || null,
            bride_parent_names: details.bride_parent_names || null,
            opening_text:       details.opening_text       || null,
            closing_text:       details.closing_text       || null,
        };
    }

    return {
        // State
        invitationId,
        currentStep,
        isSaving,
        saveError,
        lastSavedStep,

        // Step data
        basic,
        details,
        events,
        galleries,
        selectedMusic,
        customConfig,
        publish,

        // Draft restore
        restoreFromDraft,

        // Save methods
        saveStep1,
        saveStep2,
        saveStep3,
        saveStep4,
        saveStep5,
        saveStep6,

        // Helpers
        addEvent,
        removeEvent,
        moveEvent,
        addGalleryItem,
        removeGallery,
        moveGallery,
        uploadFile,
        uploadPhotoField,
        uploadAudio,
        uploadGalleryFile,
    };
}
