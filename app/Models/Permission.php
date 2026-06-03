<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'module',
        'action',
        'is_sensitive',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withTimestamps();
    }

    public function userOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeSensitive(Builder $query): Builder
    {
        return $query->where('is_sensitive', true);
    }

    public function moduleLabel(): string
    {
        return [
            'dashboard' => 'Tableau de bord',
            'validations' => 'Centre de validations',
            'users' => 'Utilisateurs',
            'roles' => 'Rôles',
            'permissions' => 'Permissions',
            'clients' => 'Clients',
            'products' => 'Produits',
            'suppliers' => 'Fournisseurs',
            'purchases' => 'Achats fournisseurs',
            'stock' => 'Stock et inventaire',
            'proformas' => 'Proformas / devis',
            'delivery_notes' => 'Bordereaux de livraison',
            'invoices' => 'Factures',
            'payments' => 'Paiements',
            'treasury' => 'Trésorerie',
            'expenses' => 'Dépenses',
            'expense_categories' => 'Catégories de charges',
            'reports' => 'Rapports',
            'activity_logs' => "Journal d'activité",
            'settings' => 'Paramètres',
            'sensitive' => 'Permissions sensibles',
        ][$this->module] ?? str($this->module)->replace('_', ' ')->title()->toString();
    }

    public function actionLabel(): string
    {
        return [
            'view' => 'Consulter',
            'create' => 'Créer',
            'update' => 'Modifier',
            'delete' => 'Supprimer',
            'delete_draft' => 'Supprimer les brouillons',
            'disable' => 'Activer / désactiver',
            'submit' => 'Soumettre à validation',
            'validate' => 'Valider',
            'reject' => 'Rejeter',
            'correct_rejected' => 'Corriger après rejet',
            'cancel' => 'Annuler',
            'export' => 'Exporter',
            'export_pdf' => 'Exporter en PDF',
            'export_excel' => 'Exporter en Excel',
            'export_receipt_pdf' => 'Exporter le reçu PDF',
            'import' => 'Importer',
            'assign' => 'Attribuer',
            'revoke' => 'Retirer',
            'assign_roles' => 'Attribuer des rôles',
            'assign_permissions' => 'Attribuer des permissions',
            'reset_password' => 'Réinitialiser le mot de passe',
            'view_balance' => 'Voir le solde client',
            'view_history' => "Voir l'historique",
            'update_purchase_price' => "Modifier le prix d'achat",
            'update_sale_price' => 'Modifier le prix de vente',
            'update_client_price' => 'Modifier les prix client',
            'update_alert_threshold' => "Modifier le seuil d'alerte",
            'view_margin' => 'Voir la marge',
            'manage_products' => 'Gérer les produits associés',
            'create_request' => "Créer une demande d'achat",
            'create_order' => 'Créer un bon de commande',
            'receive_invoice' => 'Enregistrer une facture fournisseur',
            'pay_supplier' => 'Régler un fournisseur',
            'view_physical' => 'Voir le stock physique',
            'view_reserved' => 'Voir le stock réservé',
            'view_suspense' => 'Voir le stock en suspens',
            'view_tool' => 'Voir le stock outil',
            'create_entry' => 'Entrer du stock',
            'create_exit' => 'Sortir du stock',
            'adjust' => 'Faire un ajustement',
            'validate_movement' => 'Valider un mouvement',
            'cancel_movement' => 'Annuler un mouvement',
            'close_suspense' => 'Clôturer un stock en suspens',
            'convert_to_delivery_note' => 'Convertir en BL',
            'mark_prepared' => 'Marquer préparé',
            'mark_delivered' => 'Marquer livré',
            'convert_to_invoice' => 'Créer une facture depuis le BL',
            'view_unpaid' => 'Voir les factures impayées',
            'view_paid' => 'Voir les factures payées',
            'view_daily_cash' => 'Voir la caisse journalière',
            'close_cash' => 'Clôturer la caisse',
            'close_suspense_stock' => 'Clôturer le stock en suspens',
            'create_expense' => 'Enregistrer une dépense',
            'view_sensitive' => 'Voir les charges sensibles',
            'view_sales' => 'Voir les ventes',
            'view_finance' => 'Voir les finances',
            'view_stock' => 'Voir les rapports stock',
            'view_expenses' => 'Voir les dépenses',
            'view_hr' => 'Voir les rapports RH',
            'update_company' => 'Modifier les informations société',
            'update_numbering' => 'Modifier la numérotation',
            'update_payment_modes' => 'Modifier les modes de paiement',
            'update_units' => 'Modifier les unités',
            'update_stock_rules' => 'Modifier les règles stock',
            'validate_own_documents' => 'Valider ses propres documents',
            'cancel_validated_invoice' => 'Annuler une facture validée',
            'cancel_validated_payment' => 'Annuler un paiement validé',
            'cancel_delivered_delivery_note' => 'Annuler un BL livré',
            'update_validated_document' => 'Modifier un document validé',
            'create_super_admin' => 'Créer / attribuer un super administrateur',
            'view_salaries' => 'Voir les salaires',
            'export_financial_reports' => 'Exporter les rapports financiers',
            'modify_roles_permissions' => 'Modifier les rôles et permissions',
            'access_activity_logs' => "Accéder au journal d'activité",
        ][$this->action] ?? str($this->action)->replace('_', ' ')->title()->toString();
    }

    public function displayLabel(): string
    {
        return $this->moduleLabel().' - '.$this->actionLabel();
    }

    public function helpText(): string
    {
        $module = mb_strtolower($this->moduleLabel());

        $texts = [
            'view' => "Permet d'ouvrir et consulter les informations du module {$module}.",
            'create' => "Permet d'ajouter de nouveaux éléments dans le module {$module}.",
            'update' => "Permet de modifier les informations déjà enregistrées dans {$module}.",
            'delete' => "Permet de supprimer un élément du module {$module}, lorsque les règles métier l'autorisent.",
            'delete_draft' => 'Permet de supprimer uniquement les documents encore en brouillon.',
            'disable' => "Permet d'activer ou de désactiver un élément sans supprimer son historique.",
            'submit' => "Permet d'envoyer un document au circuit de validation.",
            'validate' => "Permet d'approuver un document et de le faire avancer dans le processus.",
            'reject' => 'Permet de refuser un document avec un motif obligatoire.',
            'correct_rejected' => 'Permet de reprendre et corriger un document rejeté.',
            'cancel' => "Permet d'annuler un document selon son statut et les règles de contrôle.",
            'export' => "Permet d'exporter les données du module.",
            'export_pdf' => 'Permet de générer un document ou rapport au format PDF.',
            'export_excel' => "Permet d'exporter les données vers un fichier Excel.",
            'export_receipt_pdf' => "Permet de générer le reçu PDF d'un paiement.",
            'import' => "Permet d'importer des données en masse.",
            'assign' => 'Permet d’attribuer cette autorisation à un rôle ou un utilisateur.',
            'revoke' => 'Permet de retirer cette autorisation d’un rôle ou d’un utilisateur.',
            'assign_roles' => "Permet d'attribuer ou de retirer des rôles à un utilisateur.",
            'assign_permissions' => 'Permet de gérer les permissions accordées à un rôle ou à un utilisateur.',
            'reset_password' => 'Permet de définir un nouveau mot de passe pour un utilisateur.',
            'view_balance' => 'Permet de consulter le solde et les montants dus par un client.',
            'view_history' => "Permet de consulter l'historique des opérations liées à une fiche.",
            'update_purchase_price' => "Permet de modifier les prix d'achat des produits.",
            'update_sale_price' => 'Permet de modifier les prix de vente des produits.',
            'update_client_price' => 'Permet de gérer les prix négociés par client.',
            'update_alert_threshold' => "Permet de modifier les seuils d'alerte de stock bas.",
            'view_margin' => "Permet d'afficher les marges et informations de rentabilité.",
            'manage_products' => "Permet d'associer ou retirer des produits chez un fournisseur.",
            'create_request' => "Permet de créer une demande d'achat fournisseur.",
            'create_order' => "Permet d'établir un bon de commande fournisseur.",
            'receive_invoice' => "Permet d'enregistrer une facture reçue d'un fournisseur.",
            'pay_supplier' => "Permet d'enregistrer un règlement effectué à un fournisseur.",
            'view_physical' => 'Permet de consulter les quantités physiquement disponibles.',
            'view_reserved' => 'Permet de consulter les quantités réservées pour des documents en cours.',
            'view_suspense' => 'Permet de consulter le stock livré en attente de clôture après paiement.',
            'view_tool' => 'Permet de consulter le stock outil et les articles internes.',
            'create_entry' => "Permet d'enregistrer une entrée de stock.",
            'create_exit' => "Permet d'enregistrer une sortie manuelle de stock.",
            'adjust' => 'Permet de corriger les écarts de stock avec un motif obligatoire.',
            'validate_movement' => "Permet d'approuver un mouvement ou ajustement de stock.",
            'cancel_movement' => "Permet d'annuler un mouvement de stock non conforme.",
            'close_suspense' => 'Permet de clôturer le stock en suspens après régularisation.',
            'convert_to_delivery_note' => 'Permet de transformer une proforma validée en bordereau de livraison.',
            'mark_prepared' => 'Permet de marquer un bordereau comme préparé par le magasin.',
            'mark_delivered' => 'Permet de confirmer la livraison et de renseigner la réception client.',
            'convert_to_invoice' => "Permet de créer une facture à partir d'un BL livré.",
            'view_unpaid' => 'Permet de consulter les factures avec un reste à payer.',
            'view_paid' => 'Permet de consulter les factures entièrement réglées.',
            'view_daily_cash' => 'Permet de consulter les encaissements de la journée.',
            'close_cash' => 'Permet de clôturer la caisse après contrôle des encaissements.',
            'close_suspense_stock' => 'Permet de clôturer le stock en suspens quand la facture est soldée.',
            'create_expense' => "Permet d'enregistrer une dépense directement en trésorerie.",
            'view_sensitive' => 'Permet de consulter les dépenses sensibles comme les salaires.',
            'view_sales' => 'Permet de consulter les statistiques de ventes.',
            'view_finance' => 'Permet de consulter les rapports financiers.',
            'view_stock' => 'Permet de consulter les rapports de stock.',
            'view_expenses' => 'Permet de consulter les rapports de dépenses.',
            'view_hr' => 'Permet de consulter les rapports RH et charges sensibles.',
            'update_company' => 'Permet de modifier les informations de la société.',
            'update_numbering' => 'Permet de configurer la numérotation des documents.',
            'update_payment_modes' => 'Permet de gérer les modes de paiement disponibles.',
            'update_units' => 'Permet de gérer les unités de mesure.',
            'update_stock_rules' => 'Permet de modifier les règles de gestion du stock.',
            'validate_own_documents' => 'Permet à un utilisateur de valider ses propres documents.',
            'cancel_validated_invoice' => 'Permet d’annuler une facture déjà validée.',
            'cancel_validated_payment' => 'Permet d’annuler un paiement déjà validé.',
            'cancel_delivered_delivery_note' => 'Permet d’annuler un BL déjà livré.',
            'update_validated_document' => 'Permet de modifier un document déjà validé.',
            'create_super_admin' => 'Permet de créer ou attribuer le profil super administrateur.',
            'view_salaries' => 'Permet de consulter les dépenses et rapports liés aux salaires.',
            'export_financial_reports' => 'Permet d’exporter les rapports financiers sensibles.',
            'modify_roles_permissions' => 'Permet de modifier les rôles, permissions et exceptions utilisateur.',
            'access_activity_logs' => "Permet d'accéder au journal d'activité complet.",
        ];

        $text = $texts[$this->action] ?? "Permet d'utiliser l'action {$this->actionLabel()} dans {$module}.";

        return $this->is_sensitive ? 'Action sensible : '.$text : $text;
    }
}
