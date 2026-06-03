<div id="confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
        <p class="text-lg font-black text-slate-950">Confirmer l'action</p>
        <p id="confirm-message" class="mt-2 text-sm text-slate-600">Cette action est sensible. Voulez-vous continuer ?</p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="confirm-cancel" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"><i data-lucide="x" class="h-4 w-4"></i>Annuler</button>
            <button type="button" id="confirm-ok" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white"><i data-lucide="check" class="h-4 w-4"></i>Confirmer</button>
        </div>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('confirm-modal');
        const message = document.getElementById('confirm-message');
        const cancel = document.getElementById('confirm-cancel');
        const ok = document.getElementById('confirm-ok');
        let pendingForm = null;

        function inferredMessage(form) {
            const explicit = form?.dataset?.confirm;
            if (explicit) return explicit;

            const method = form?.querySelector('input[name="_method"]')?.value?.toUpperCase();
            const action = form?.getAttribute('action') || '';

            if (method === 'DELETE') return 'Cette suppression est sensible. Voulez-vous continuer ?';
            if (action.includes('/reject')) return 'Confirmer le rejet de ce document ?';
            if (action.includes('/validate')) return 'Confirmer la validation de ce document ?';
            if (action.includes('/mark-delivered')) return 'Confirmer la livraison et son impact stock ?';
            if (action.includes('/mark-prepared')) return 'Confirmer la préparation de ce document ?';
            if (action.includes('/stock/movements')) return 'Confirmer ce mouvement de stock ?';
            if (action.includes('/reset-password')) return 'Confirmer la réinitialisation du mot de passe ?';
            if (action.includes('/toggle-active')) return 'Confirmer le changement de statut de cet utilisateur ?';

            return null;
        }

        document.addEventListener('submit', event => {
            const form = event.target;
            const confirmMessage = inferredMessage(form);
            if (!confirmMessage || form.dataset.confirmed === '1') return;

            event.preventDefault();
            pendingForm = form;
            message.textContent = confirmMessage;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        function closeModal() {
            pendingForm = null;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        cancel?.addEventListener('click', closeModal);
        modal?.addEventListener('click', event => {
            if (event.target === modal) closeModal();
        });

        ok?.addEventListener('click', () => {
            if (!pendingForm) return;
            pendingForm.dataset.confirmed = '1';
            pendingForm.submit();
        });
    })();
</script>
