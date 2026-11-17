# Script d'assemblage du Rapport (`build_rapport.py`)

Ce script Python analyse l'arborescence des dossiers du rapport, récupère le contenu de chaque fichier Markdown (`.md`), réajuste les chemins relatifs des images pour éviter les liens brisés, et assemble le fichier final **`rapport-final.md`** conformément à la structure définie dans **`skeleton.md`**.

---

## 📋 Fonctionnalités

1. **Respect de l'architecture du rapport** : Utilise l'ordre et la hiérarchie de `skeleton.md`.
2. **Gestion automatique des images** : Convertit les chemins relatifs des images Markdown (`![alt](chemin)`) et HTML (`<img src="chemin">`) afin qu'ils pointent correctement vers leurs sous-dossiers respectifs depuis `rapport-final.md`.
3. **Sécurité & Sauvegarde** : Si `rapport-final.md` existe déjà, une sauvegarde automatique nommée `rapport-final.md.bak` est créée avant toute modification.
4. **Conservation des sections existantes** : Si une section contient déjà du texte dans `rapport-final.md` mais qu'aucun fichier `.md` n'est présent dans le dossier correspondant, le texte existant est conservé.
5. **Rapport détaillé dans le terminal** : Affiche à la fin :
   - Les parties modifiées / mises à jour.
   - Les parties inchangées.
   - Les parties vides (à compléter).

---

## 🚀 Utilisation manuelle

### 1. Prérequis
Assurez-vous que Python 3 est installé sur votre machine :
```bash
python --version
# ou
python3 --version
```

### 2. Exécution du script
Ouvrez un terminal dans le dossier du projet ou dans le dossier `rapport/` :

```bash
# Depuis la racine du projet :
python rapport/build_rapport.py

# Ou depuis le dossier rapport :
cd rapport
python build_rapport.py
```

Le fichier `rapport-final.md` sera généré ou mis à jour automatiquement au même niveau que le script.

---

## 🤖 Prompt à donner à un Agent IA

Copiez et collez le prompt suivant à n'importe quel agent IA (Antigravity, Cursor, Claude Code, Copilot, etc.) :

```markdown
Vérifie si Python est installé sur l'environnement en exécutant `python --version` (ou `python3 --version`). 
Si Python n'est pas installé, installe Python 3 selon l'OS (avec winget/choco sur Windows, apt/apk sur Linux, ou brew sur macOS).
Ensuite, exécute le script `build_rapport.py` situé dans le dossier du rapport pour générer/mettre à jour `rapport-final.md` et affiche le résumé des sections modifiées, inchangées et vides.
```

---

## 📁 Structure attendue des dossiers

```
rapport/
│
├── skeleton.md                       # Définition de l'ordre et des sections
├── build_rapport.py                  # Script Python d'assemblage
├── rapport-final.md                  # Fichier assemblé final généré
├── rapport-final.md.bak              # Sauvegarde de la version précédente
│
├── Méthodologie Scrum/
│   ├── methode_scrum.md
│   └── scrum.png
├── Processus 2TUP/
│   ├── rapport-methode-agile-template.md
│   └── images.jpg
├── Design Thinking/
│   ├── design_thinking.md
│   └── design_thinking.jpg
└── [Autres dossiers de sections...]
```
