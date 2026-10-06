import os
import re
import time
import requests
import pandas as pd
from urllib.parse import urlparse

# Fichiers d'entrée et de sortie
FICHIER_ENTREE = "LISTE DES ENTREPRISES.xlsx"
FICHIER_SORTIE = "LISTE_DES_ENTREPRISES_ENRICHIE.xlsx"

# Headers pour ne pas être bloqué lors des requêtes web
HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
}

def chercher_site_web(denomination, ville):
    """
    Recherche l'URL du site officiel de l'entreprise via DuckDuckGo HTML.
    """
    query = f"{denomination} {ville} site officiel"
    url = f"https://html.duckduckgo.com/html/?q={requests.utils.quote(query)}"
    
    # Domaines d'annuaires à ignorer pour cibler le vrai site de l'entreprise
    blacklist = [
        "societe.com", "pagesjaunes.fr", "infogreffe.fr", "verif.com", 
        "pappers.fr", "annuaire-entreprises.data.gouv.fr", "linkedin.com",
        "facebook.com", "instagram.com", "kompass.com", "figaro.fr"
    ]
    
    try:
        resp = requests.post(url, data={'q': query}, headers=HEADERS, timeout=8)
        if resp.status_code == 200:
            # Extraction des liens de résultats DuckDuckGo
            links = re.findall(r'<a class="result__url" href="([^"]+)"', resp.text)
            for link in links:
                if not link.startswith("http"):
                    link = "https://" + link.strip()
                domain = urlparse(link).netloc.lower()
                if not any(b in domain for b in blacklist):
                    return link.strip()
    except Exception:
        pass
    return None

def chercher_email(site_url):
    """
    Tente d'extraire une adresse email de contact sur la page d'accueil ou la page contact.
    """
    if not site_url:
        return None
        
    pages_to_check = [site_url]
    parsed = urlparse(site_url)
    base_url = f"{parsed.scheme}://{parsed.netloc}"
    pages_to_check.append(f"{base_url}/contact")
    
    # Extensions et mots-clés à ignorer pour éviter les faux positifs (ex: image.png)
    ignored_exts = ('.png', '.jpg', '.jpeg', '.webp', '.svg', '.gif', '.css', '.js')
    
    regex_email = r'[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+'
    
    for page in pages_to_check:
        try:
            r = requests.get(page, headers=HEADERS, timeout=6)
            if r.status_code == 200:
                emails = re.findall(regex_email, r.text)
                for mail in emails:
                    mail_clean = mail.lower().strip()
                    if not mail_clean.endswith(ignored_exts) and "sentry" not in mail_clean:
                        return mail_clean
        except Exception:
            continue
    return None

def traiter_fichier():
    if not os.path.exists(FICHIER_ENTREE):
        print(f"Erreur : Impossible de trouver '{FICHIER_ENTREE}' dans le dossier actuel.")
        return

    print(f"Chargement de {FICHIER_ENTREE}...")
    xls = pd.ExcelFile(FICHIER_ENTREE)
    writer = pd.ExcelWriter(FICHIER_SORTIE, engine="openpyxl")

    for sheet_name in xls.sheet_names:
        df = pd.read_excel(FICHIER_ENTREE, sheet_name=sheet_name)
        print(f"\n--- Traitement de l'onglet : {sheet_name} ({len(df)} lignes) ---")
        
        # Identification du nom exact de la colonne URL
        url_col = "site URL" if "site URL" in df.columns else "SITE URL"
        mail_col = "mail"

        for idx, row in df.iterrows():
            nom = str(row['denomination']).strip()
            ville = str(row['codePostal']).strip()
            
            # Si le site est déjà renseigné, on le conserve, sinon on cherche
            site_existant = str(row[url_col]) if pd.notna(row[url_col]) else ""
            mail_existant = str(row[mail_col]) if pd.notna(row[mail_col]) else ""
            
            site = site_existant
            mail = mail_existant

            if not site or site == "nan":
                print(f"[{idx+1}/{len(df)}] Recherche site pour : {nom}...")
                site = chercher_site_web(nom, ville)
                if site:
                    df.at[idx, url_col] = site
                    print(f"   -> Site trouvé : {site}")

            if (not mail or mail == "nan") and site:
                print(f"   -> Recherche email sur : {site}...")
                mail = chercher_email(site)
                if mail:
                    df.at[idx, mail_col] = mail
                    print(f"   -> Email trouvé : {mail}")

            # Pause courte pour ne pas se faire bannir par les moteurs de recherche
            time.sleep(1)

        df.to_excel(writer, sheet_name=sheet_name, index=False)
        print(f"Onglet {sheet_name} terminé !")

    writer.close()
    print(f"\nTerminé avec succès ! Fichier sauvegardé sous : {FICHIER_SORTIE}")

if __name__ == "__main__":
    traiter_fichier()