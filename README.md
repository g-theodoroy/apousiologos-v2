# Ηλεκτρονικός απουσιολόγος v2


## Σκοπός: 

- Η καταγραφή των απουσιών των μαθητών κάθε ώρα σε **πραγματικό χρόνο**. Άμεση **εποπτεία** των απόντων μαθητών από την 1η ώρα και κάθε ώρα.  **Εισαγωγή των απουσιών στο myschool** άμα τη λήξη των μαθημάτων (εξαγωγή αρχείου xls).

- **Ο προγραμματισμός** των διαγωνισμάτων

- **Η καταχώριση** της βαθμολογίας

## Εγκατάσταση

#### linux terminal

```
git clone https://github.com/g-theodoroy/apousiologos-v2.git
cd apousiologos-v2
composer install --no-dev
chmod -R 0777 storage/
```

#### windows

Κατεβάστε το zip:

https://github.com/g-theodoroy/apousiologos-v2/archive/refs/heads/main.zip

Αποσυμπιέστε και με το **cmd** πηγαίνετε στον φάκελο και τρέξτε:
```
composer install --no-dev
```

Φυσικά θα πρέπει να έχετε εγκατεστημένη την **php** και τον **composer**

https://www.hostinger.com/tutorials/how-to-install-composer


Αν θέλετε να ρυθμίσετε αποστολή **email** συμπληρώστε κατάλληλα στο αρχείο **.env**:

```
MAIL_MAILER=smtp
MAIL_HOST=xxxxxxxxxx
MAIL_PORT=587
MAIL_USERNAME=xxxxxxxxxx
MAIL_PASSWORD=xxxxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=xxxxxxxxxx
MAIL_FROM_NAME="${APP_NAME}"
```

**Ανεβάστε τον φάκελο στον server**


## Παρατηρήσεις για την έκδοση της Php (ΙΑΝ 2023)

- Αν σκοπεύετε να τρέξετε το "πρόγραμμα" στον **webhost.sch.gr** πρέπει να κάνετε εγκατάσταση με **Php 8.0**. Είναι η έκδοση που υποστηρίζεται αυτή τη στιγμή από τους servers του ΠΣΔ.
- Σε δικό σας server με **Php 8.1** ο composer εγκαθιστά τις βιβλιοθήκες κανονικά
- Σε δικό σας server με **Php 8.2** ο composer διαμαρτύρεται για τις εξαρτήσεις. Ξεπερνάμε το "πρόβλημα" με `composer install --no-dev --ignore-platform-reqs
`



## Ρύθμιση - χρήση

[Οδηγίες ρύθμισης κ χρήσης Ηλ.Απουσιολόγου.pdf](https://drive.google.com/file/d/17s1Oc0WNlOfuaPti7Tkr7cU3eop89RAP/view?usp=sharing)


# GΘ@IAN_2023



