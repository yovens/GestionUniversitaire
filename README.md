# ITAC — Gestion universitaire multi-facultés

Réécriture complète en **Laravel 12** de l'ancienne application PHP « gestionItac ».
Elle gère plusieurs facultés (Informatique, Génie civil, Agronomie, Sciences administratives…)
avec, pour chacune : classes, cours, professeurs, inscriptions, examens, notes, frais de scolarité,
paiements, bulletins et calendrier.

---

## Rezime an kreyòl

- Chak **fakilte** gen pwòp klas li, kou li, frè li (vèsman), ak pwòp **sèy reyisit** li.
- **5 wòl** : Administratè (tout inivèsite a), Dwayen (sèlman fakilte pa l), Sekretè (fakilte pa l oswa tout si se sekretarya santral), Pwofesè (sèlman kou yo ba li), Etidyan.
- Pwofesè antre nòt yo sou yon **griy** pou tout klas la. Lè sekretarya **pibliye** yon egzamen, pwofesè pa ka chanje nòt yo ankò, epi etidyan ki **ajou nan peman** yo ka wè nòt yo.
- Chak peman bay yon **resi nimewote** (PDF). Yon peman pa janm efase : li **anile** ak yon rezon, e sa ekri nan jounal la.
- **Bilten**, **pwosè-vèbal** delibera ak **resi** soti an PDF.
- Sekirite : modpas yo **chifre** (bcrypt), limit tantativ koneksyon, CSRF, fichye prive, chak aksyon enpòtan ekri nan **jounal aktivite**.
- Pou mete ansyen done yo : `php artisan itac:importer-ancienne-base` (gade anba a).

---

## Fonctionnalités

| Domaine | Ce que fait l'application |
|---|---|
| Facultés | Code, couleur d'identification, seuil de réussite propre à chaque faculté |
| Années académiques | Une année « courante » qui pilote inscriptions, frais, examens |
| Structure | Classes (niveaux) par faculté, cours avec coefficient et session, affectation d'un professeur par cours et par année |
| Inscriptions | Matricule automatique `CODE-AA-0001`, compte étudiant créé avec mot de passe provisoire, réinscription annuelle, statuts (inscrit, admis, reprise, abandon) |
| Examens | Intra / final / reprise, par session, commun ou propre à une faculté, ajout automatique au calendrier, **publication** des notes |
| Notes | Grille de saisie par classe (clavier : Entrée = suivant), virgule décimale acceptée, absents, verrouillage après publication, historique avant/après dans le journal |
| Résultats | Moyenne pondérée, reprise qui remplace la moyenne si meilleure, rang, décision, procès-verbal PDF, validation des décisions en fin d'année |
| Finances | Échéancier (versements) par faculté et par année, encaissement avec reçu PDF numéroté, justificatif, annulation motivée, export CSV pour Excel |
| Accès aux notes | Un examen peut exiger un versement : l'étudiant voit ses notes seulement quand il a payé le cumul requis |
| Calendrier | Vue mensuelle, rentrées, examens, reprises, congés, par faculté ou pour tous |
| Espace professeur | Ses cours, la liste et les notes de ses étudiants, dépôt des sujets d'examen (visibles par le secrétariat) |
| Espace étudiant | Notes publiées, bulletin PDF, échéancier, reçus |
| Administration | Personnel, réinitialisation des mots de passe, désactivation de comptes, journal d'activité |

### Règles de calcul (modifiables dans `.env` / `config/itac.php`)

- Moyenne d'un cours = **intra × 40 % + final × 60 %** (`ITAC_POIDS_INTRA`, `ITAC_POIDS_FINAL`).
  Si un seul des deux existe, il compte seul (résultat provisoire).
- Une note de **reprise** remplace la moyenne du cours si elle est meilleure.
- Un **absent** compte 0.
- Moyenne générale = Σ(moyenne × coefficient) / Σ coefficients.
- **Admis(e)** si moyenne générale ≥ seuil de la faculté **et** aucun cours sous le seuil ; sinon **En reprise**.
- Les résultats sont **partiels** tant qu'un cours n'a pas sa note finale (ou de reprise).

### Rôles

| Rôle | Périmètre |
|---|---|
| Administrateur | Toute l'université, facultés, années, personnel, journal |
| Doyen | Sa faculté : structure (classes, cours, affectations), frais, scolarité, décisions |
| Secrétaire | Sa faculté (ou toutes si aucune faculté n'est indiquée) : inscriptions, paiements, examens, notes, bulletins, calendrier |
| Professeur | Uniquement les cours qui lui sont affectés |
| Étudiant | Son propre dossier |

---

## Installation (développement)

Prérequis : PHP 8.2+ (extensions `pdo_mysql`, `mbstring`, `gd`, `intl`, `zip`, `fileinfo`), Composer, MySQL 8 / MariaDB 10.6+.

```bash
composer install
cp .env.example .env          # puis renseignez DB_*, APP_URL…
php artisan key:generate
php artisan migrate
php artisan itac:installer    # crée l'administrateur et l'année académique courante
php artisan serve
```

Base de démonstration (4 facultés, professeurs, 64 étudiants, notes et paiements — mot de passe `password1`) :

```bash
php artisan migrate:fresh --seeder=DemoSeeder --seed
```

Comptes de démo : `admin`, `secretariat`, `doyen.info`, `doyen.gc`, `prof1` … `prof8`, étudiants `info-26-0001`…

Tests automatiques :

```bash
php artisan test
```

---

## Mise en production

1. **`.env`** : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` en `https://…`, identifiants MySQL.
   Sans HTTPS, mettez temporairement `SESSION_SECURE_COOKIE=false` (sinon la connexion échoue).
2. Installer et optimiser :
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate        # une seule fois
   php artisan migrate --force
   php artisan itac:installer      # une seule fois
   php artisan optimize            # met en cache config, routes, vues
   ```
3. **Le serveur web doit pointer sur le dossier `public/`**, jamais sur la racine du projet
   (sinon `.env` serait lisible). Sur un hébergement mutualisé, placez le projet hors de `public_html`
   et faites pointer `public_html` vers `public/`.
4. Droits d'écriture : `storage/` et `bootstrap/cache/` (utilisateur du serveur web).
   ```bash
   chown -R www-data:www-data storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache
   ```
5. **Cron** (sauvegarde quotidienne de la base à 2 h 30, 14 jours conservés) :
   ```
   * * * * * cd /var/www/itac && php artisan schedule:run >> /dev/null 2>&1
   ```
   Sauvegarde manuelle : `php artisan itac:sauvegarde`. Copiez régulièrement
   `storage/app/private/sauvegardes/` hors du serveur.
6. Après chaque mise à jour du code : `php artisan migrate --force && php artisan optimize`.
   Pendant la mise à jour : `php artisan down` puis `php artisan up`.

Exemple Nginx :

```nginx
server {
    listen 443 ssl;
    server_name gestion.itac.edu.ht;
    root /var/www/itac/public;
    index index.php;
    client_max_body_size 8M;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
    location ~ /\.(?!well-known) { deny all; }
}
```

Avec Apache, le fichier `public/.htaccess` fourni suffit (activer `mod_rewrite`).

---

## Reprendre les données de l'ancienne application

1. Gardez l'ancienne base `itac_db` accessible et renseignez `LEGACY_DB_*` dans `.env`.
2. Vérifiez d'abord ce qui sera importé :
   ```bash
   php artisan itac:importer-ancienne-base --simulation
   ```
3. Importez (les anciennes données sont rattachées à une faculté, par défaut `INFO`) :
   ```bash
   php artisan itac:importer-ancienne-base --faculte=INFO --faculte-nom="Sciences informatiques" --annee=2025-2026
   ```
4. Les anciens mots de passe étaient stockés **en clair** : ils ne sont pas repris.
   Chaque compte reçoit un mot de passe provisoire, listé dans un fichier CSV
   (`storage/app/private/import-mots-de-passe-….csv`). Distribuez-les puis **supprimez ce fichier**.
   Chacun devra choisir son mot de passe à la première connexion.

---

## Sécurité — ce qui a été corrigé par rapport à l'ancienne version

| Ancienne version | Nouvelle version |
|---|---|
| Mots de passe en clair dans la base | Hachage bcrypt, mot de passe provisoire à changer à la 1re connexion |
| Mot de passe MySQL `root` écrit dans le code | Configuration dans `.env`, hors du dossier public |
| Aucune protection CSRF | Jeton CSRF sur tous les formulaires |
| Aucune limite de tentatives de connexion | 5 essais par identifiant et par IP, puis attente |
| Contrôle de rôle oublié sur certaines pages (`header()` sans `exit`) | Middleware de rôle + contrôle de faculté sur chaque action |
| Téléversement sans contrôle (on pouvait envoyer un `.php`) | Types et tailles vérifiés, noms aléatoires, fichiers hors du dossier public, accès contrôlé |
| Champs affichés sans échappement (XSS) | Échappement systématique par Blade |
| Suppression directe des paiements et des notes | Paiements annulés (jamais supprimés), journal avant/après de chaque note |
| Erreurs SQL affichées à l'écran | Pages d'erreur propres, détails dans les journaux uniquement |
| — | En-têtes de sécurité (CSP, X-Frame-Options, HSTS…), sessions chiffrées, cookies sécurisés |

---

## Organisation du code

```
app/
  Enums/Role.php                 rôles
  Models/                        Faculte, AnneeAcademique, Classe, Matiere, Affectation, Inscription,
                                 Examen, Note, TrancheFrais, Paiement, Evenement, SujetExamen, JournalActivite
  Services/ResultatService.php   moyennes, décisions, délibération
  Services/FinanceService.php    échéancier, solde, accès aux notes, numéros de reçu
  Services/Journal.php           journal d'audit
  Http/Controllers/              Admin/, Structure/, Scolarite/, Prof/, Etudiant/
  Http/Middleware/               rôle, compte actif / mot de passe, en-têtes de sécurité
  Console/Commands/              itac:installer, itac:importer-ancienne-base, itac:sauvegarde
resources/views/                 interface (Blade), PDF (pdf/), pages d'erreur (errors/)
public/css/app.css, public/js/app.js   styles et scripts (aucune compilation nécessaire)
tests/Feature/                   tests de navigation, de métier et de parcours complet
```
