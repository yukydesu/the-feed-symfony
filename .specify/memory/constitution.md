# The Feed Constitution

## Core Principles

### I. Stack Technique Fixe
Le projet The Feed utilise exclusivement les technologies suivantes :
- **Framework** : Symfony (dernière version stable)
- **Template Engine** : Twig
- **ORM** : Doctrine
- **Base de données** : MySQL
- **JavaScript** : Vanilla JS (pas de frameworks frontend)
- **Routing JS** : FOSJsRoutingBundle
- **Paiement** : Stripe PHP SDK
- **Environnement** : Docker (container `thefeed-backend`)

### II. Respect Strict du TD3
- Aucune fonctionnalité non demandée dans le TD3
- Aucune invention ou ajout personnel
- Code minimal mais correct
- Style et conventions du cours Symfony à respecter absolument
- Utiliser les makers Symfony (`make:entity`, `make:migration`, `make:command`, etc.) dès que le TD l'exige
- Ne jamais créer manuellement un fichier si un maker existe

### III. Exécution Docker Obligatoire
Toutes les commandes Symfony doivent être exécutées via :
```bash
docker exec -it thefeed-backend bash
php bin/console <commande>
```

### IV. Sécurité et Permissions
- Utilisation des **Voters** pour la logique d'autorisation
- Annotations **IsGranted** avec ExpressionLanguage
- Hiérarchie de rôles : `ROLE_ADMIN` hérite de `ROLE_USER`
- Vérifications de propriété (user == auteur) via Voters
- Pas de vérifications manuelles 403/404 dans les controllers quand un Voter existe

### V. Logique Premium
- Champ booléen `premium` sur l'entité User (défaut: false)
- Groupes de validation dynamiques selon statut premium :
  - Non premium : `Length(max=50)`
  - Premium : `Length(max=200)`
- Prix premium configuré dans `services.yaml` (parameter `premium_price`)
- Commandes CLI pour gestion premium/admin :
  - `give:premium`
  - `revoke:premium`
  - `promote:admin`
  - `revoke:admin`

### VI. Intégration Stripe
- Flow complet : Checkout → Confirmation → Webhook
- Mode `payment` avec metadata `userId` (et `studentToken` si nécessaire)
- Vérification de paiement avant upgrade premium
- Webhook avec vérification de signature
- Gestion des redirections success/cancel

### VII. JavaScript Asynchrone
- Suppression AJAX des publications via `fetch()` DELETE
- Manipulation DOM immédiate après succès
- Chargement conditionnel des scripts JS via Twig
- Utilisation de `Routing.generate()` pour les URLs côté client
- Gestion propre des erreurs (403, 404, 500)

## Contraintes de Développement

### Code Quality
- Code lisible et maintenable
- Commentaires uniquement si nécessaire (code auto-documenté privilégié)
- Respect des conventions Symfony (PSR, best practices)
- Pas de code mort ou commenté

### Validation et Tests
- Validateurs Symfony pour toutes les entités
- Groupes de validation selon contexte (premium/non-premium)
- Tests manuels via interface web requis
- Vérification du comportement attendu pour chaque fonctionnalité

### Architecture
- Respect MVC strict
- Controllers légers, logique métier dans les services si nécessaire
- Repositories pour les requêtes personnalisées
- Services injectés via autowiring

## Workflow de Développement

### Respect du Workflow SpecKit
1. Constitution établie (ce fichier)
2. `/speckit.specify` → Spécifications fonctionnelles pures (pas de tech)
3. Nettoyage de tous les `NEEDS_CLARIFICATION`
4. `/speckit.plan` → Plan technique avec stack définie
5. `/speckit.tasks` → Liste des tâches numérotées
6. Implémentation en suivant `tasks.md` strictement
7. Mise à jour de `tasks.md` et `cloud.md` au fur et à mesure

### Commandes Symfony à Privilégier
- `make:entity` pour créer/modifier les entités
- `make:migration` puis `doctrine:migrations:migrate` pour le schéma DB
- `make:controller` pour les controllers
- `make:command` pour les commandes CLI
- `make:voter` pour les voters

### Format de Réponse Attendu
Pour chaque étape d'implémentation :
```
### Étape X – Nom de l'étape

**Objectif** : Description en 2 lignes max

**Tâches SpecKit** : T00xx, T00yy

**Commandes Docker + Symfony** :
docker exec -it thefeed-backend bash
php bin/console <commande>

**Code COMPLET** : Fichiers entiers modifiés
```

## Gouvernance

### Règles Non Négociables
- Cette constitution prévaut sur toute autre considération
- Toute modification doit être justifiée et documentée
- Le TD3 est la référence absolue du périmètre fonctionnel
- Aucune complexité non justifiée par le TD3

### Résolution d'Ambiguïtés
En cas d'ambiguïté ou de clarification nécessaire :
1. Consulter le TD3 complet
2. Appliquer les conventions Symfony standard
3. Privilégier la simplicité
4. Documenter la décision dans `spec.md` ou `plan.md`

### Validation
- Chaque fonctionnalité doit être testée manuellement
- Toutes les cases de vérification SpecKit doivent être cochées
- Le code doit être conforme au style du cours
- Aucune erreur ni warning acceptable

**Version**: 1.0.0 | **Ratified**: 2025-12-10 | **Last Amended**: 2025-12-10
