# Hjemmeside-template
En template til at starte en ny hjemmeside, så de mest standard ting er sat op.

Indeholder basic plugins, som jeg altid bruger og har 2 versioner:
* Ren .htaccess
* Rasmus's index.php

Der ligger også en `deploy.php`, som kan bruges til automatisk deploy fra et hostet git-projekt.  
Bruges ved at lave et web-hook, som pejer på `deploy.php` og brugeren, som eksekverer filen (typisk `www-data`), skal være sat op til at have adgang til projektet med SSH.  
Projektet skal også være klonet fra git af samme bruger som eksekverer `deploy.php`, fx med kommandoen:
```
git clone git@github.com:theme1256/template .
```

### Plugins

* PHP
  - HTML2Text ([Link til projekt](https://github.com/mtibben/html2text))
  - Mobile Detect 2.8.24 ([Link til projekt](https://packagist.org/packages/mobiledetect/mobiledetectlib))
* JS & CSS
  - jQuery 1.12.4 ([Link til projekt](https://jquery.com/))
  - Bootstrap 3.3.7 ([Link til projekt](http://getbootstrap.com/))
  - Font Awesome 4.7.0 ([Link til projekt](http://fontawesome.io/))

## Setup
Hvad skal der til før siden virker?

1. Upload `assets` og `etc` til roden af html-mappen
2. Beslut om der skal bruges `htaccess` eller `index` version
3. Upload indeholdet fra den valgte versions mappe til roden af html-mappen
   * Hvis det vælges at bruge `index`, så skal `Redirect.sql` eksekveres på serveren i den database som siden skal bruge
4. Kopier `etc/sample_conf.php` til `etc/conf.php` og udfyld variablerne

### Guide til htaccess-version

Virker ved at fjerne `.php` for enden af alle url'er, men stadig rammer php-filerne.  
Virker rigtig godt hvis man har mange hybride url'er.  
Tjek `.htaccess` filen for eksempler og hvor rewrites skal sættes ind.

Filerne på serveren skal ligge på samme måde som url'erne bliver.

### Guide til index-version

Virker ved at redirecte alle request til `index.php`, som laver et databaseopslag for at finde ud af hvilken fil der skal vises.  
Tillader også at have nemt ved at lave et web-interface til at lave urler som redirecter til andre filer og derved have forskellige url'er som gør det samme.  
Virker rigtig godt, hvis der ikke er så mange hybride url'er og man ikke har noget imod at vise dem.

Url'er skal oprettes i `Redirect` tabellen, hvor `kildeUrl` er den url, som brugeren skriver, `destinationUrl` er den fil, som `index.php` skal læse og vise, `fk_urlID` bruges kun, hvis `type` er `0`, da den viser hvilken anden url, som der redirectes til. `fk_urlID` skal ikke være sat, hvis den linje er et redirect til en ekstern side. `beskrivelse` er bare en note, så det er nemmere at finde rundt i senere.

Filerne på serveren skal ikke ligge på samme måde som url'erne er.