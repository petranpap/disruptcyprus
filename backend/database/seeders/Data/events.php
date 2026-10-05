<?php

/**
 * Demo events (fictional). `when` places the event relative to the seeding time:
 * past, this_week, this_month (after this week) or later. `slot` spreads events inside that window.
 */
return [
    ['when' => 'past', 'days' => -20, 'hour' => 9, 'hours' => 8, 'city' => 'Limassol', 'venue' => 'Marina Conference Centre', 'industries' => ['fintech', 'blockchain'], 'price' => ['en' => 'From €120', 'el' => 'Από €120'],
        'en' => ['Cyprus FinTech Summit 2026', 'A full day of talks and demos on payments, regtech and digital assets, with regulators and founders on the same stage.'],
        'el' => ['Cyprus FinTech Summit 2026', 'Ολοήμερη εκδήλωση με ομιλίες και παρουσιάσεις για πληρωμές, regtech και ψηφιακά περιουσιακά στοιχεία, με εποπτικές αρχές και ιδρυτές στην ίδια σκηνή.']],
    ['when' => 'past', 'days' => -9, 'hour' => 19, 'hours' => 2, 'city' => 'Nicosia', 'venue' => 'Old Town Innovation Hub', 'industries' => ['artificial-intelligence'], 'price' => ['en' => 'Free', 'el' => 'Δωρεάν'],
        'en' => ['AI Meetup Nicosia #14', 'Lightning talks on running language models locally, followed by networking.'],
        'el' => ['AI Meetup Λευκωσίας #14', 'Σύντομες ομιλίες για την τοπική εκτέλεση γλωσσικών μοντέλων και δικτύωση.']],
    ['when' => 'past', 'days' => -3, 'hour' => 8, 'hours' => 2, 'city' => 'Limassol', 'venue' => 'Seafront Co-working', 'industries' => ['funding-venture-capital'], 'price' => ['en' => 'Free, registration required', 'el' => 'Δωρεάν, με εγγραφή'],
        'en' => ['Founders Breakfast Limassol', 'An informal breakfast for founders and operators to swap notes on hiring and fundraising.'],
        'el' => ['Πρωινό Ιδρυτών στη Λεμεσό', 'Χαλαρό πρωινό για ιδρυτές και στελέχη για ανταλλαγή εμπειριών σε προσλήψεις και άντληση κεφαλαίων.']],

    ['when' => 'this_week', 'slot' => 0.2, 'hour' => 18, 'hours' => 3, 'city' => 'Larnaca', 'venue' => 'Port Tech Quarter', 'industries' => ['funding-venture-capital', 'b2b-saas'], 'featured' => true, 'price' => ['en' => 'Free', 'el' => 'Δωρεάν'],
        'en' => ['Pitch Night: Seed Edition', 'Eight seed-stage startups pitch to a panel of angels and regional funds. Audience vote decides the community award.'],
        'el' => ['Pitch Night: Seed Edition', 'Οκτώ startups σε στάδιο seed παρουσιάζονται σε angels και περιφερειακά funds. Το κοινό ψηφίζει για το βραβείο κοινότητας.']],
    ['when' => 'this_week', 'slot' => 0.5, 'hour' => 12, 'hours' => 1, 'online' => true, 'industries' => ['quantum', 'artificial-intelligence', 'space'], 'price' => ['en' => 'Free', 'el' => 'Δωρεάν'],
        'en' => ['Webinar: EU Funding for Deep Tech', 'How to prepare a competitive application for European deep-tech funding, with a Q&A with past grant winners.'],
        'el' => ['Webinar: Ευρωπαϊκή χρηματοδότηση για Deep Tech', 'Πώς να ετοιμάσετε ανταγωνιστική αίτηση για ευρωπαϊκή χρηματοδότηση βαθιάς τεχνολογίας, με ερωτήσεις προς προηγούμενους δικαιούχους.']],
    ['when' => 'this_week', 'slot' => 0.8, 'hour' => 10, 'hours' => 7, 'city' => 'Limassol', 'venue' => 'Maritime Innovation Centre', 'industries' => ['maritime', 'cleantech'], 'price' => ['en' => '€60', 'el' => '€60'],
        'en' => ['Maritime Innovation Forum', 'Ship managers, ports and startups discuss decarbonisation, autonomous vessels and digital crew services.'],
        'el' => ['Φόρουμ Ναυτιλιακής Καινοτομίας', 'Διαχειριστές πλοίων, λιμάνια και startups συζητούν για απανθρακοποίηση, αυτόνομα πλοία και ψηφιακές υπηρεσίες πληρωμάτων.']],

    ['when' => 'this_month', 'slot' => 0.1, 'hour' => 18, 'hours' => 3, 'city' => 'Nicosia', 'venue' => 'Innovation Hub', 'industries' => ['social-impact', 'work-productivity'], 'price' => ['en' => 'Free', 'el' => 'Δωρεάν'],
        'en' => ['Women in Tech Cyprus Meetup', 'Talks and mentoring circles on career growth, leadership and starting a company.'],
        'el' => ['Women in Tech Cyprus Meetup', 'Ομιλίες και κύκλοι mentoring για εξέλιξη καριέρας, ηγεσία και ίδρυση εταιρείας.']],
    ['when' => 'this_month', 'slot' => 0.35, 'hour' => 9, 'hours' => 30, 'city' => 'Limassol', 'venue' => 'College Campus', 'industries' => ['smart-city', 'iot', 'edtech'], 'featured' => true, 'price' => ['en' => 'Free for students', 'el' => 'Δωρεάν για φοιτητές'],
        'en' => ['Smart Campus Hackathon', 'A 30-hour hackathon to build solutions for energy, mobility and student life on campus. Teams of 3–5.'],
        'el' => ['Smart Campus Hackathon', 'Hackathon 30 ωρών για λύσεις ενέργειας, μετακίνησης και φοιτητικής ζωής στην πανεπιστημιούπολη. Ομάδες 3–5 ατόμων.']],
    ['when' => 'this_month', 'slot' => 0.6, 'hour' => 9, 'hours' => 8, 'city' => 'Nicosia', 'venue' => 'Conference Centre', 'industries' => ['cybersecurity', 'enterprise-ict'], 'price' => ['en' => '€90', 'el' => '€90'],
        'en' => ['Cybersecurity Day Cyprus', 'Incident response, cloud security and compliance under NIS2, with hands-on workshops in the afternoon.'],
        'el' => ['Ημέρα Κυβερνοασφάλειας Κύπρου', 'Αντιμετώπιση περιστατικών, ασφάλεια cloud και συμμόρφωση με την NIS2, με πρακτικά εργαστήρια το απόγευμα.']],
    ['when' => 'this_month', 'slot' => 0.9, 'hour' => 17, 'hours' => 2, 'online' => true, 'industries' => ['funding-venture-capital'], 'price' => ['en' => 'Free', 'el' => 'Δωρεάν'],
        'en' => ['Startup Legal Clinic', 'Lawyers answer founders’ questions on shareholder agreements, ESOPs and IP assignment.'],
        'el' => ['Νομική Κλινική για Startups', 'Δικηγόροι απαντούν σε ερωτήσεις ιδρυτών για συμφωνίες μετόχων, προγράμματα μετοχών και πνευματική ιδιοκτησία.']],

    ['when' => 'later', 'days' => 35, 'hour' => 9, 'hours' => 9, 'city' => 'Limassol', 'venue' => 'Marina Conference Centre', 'industries' => ['blockchain', 'fintech'], 'price' => ['en' => 'From €150', 'el' => 'Από €150'],
        'en' => ['Cyprus Blockchain Week', 'Three days of talks on tokenisation, MiCA compliance and on-chain finance.'],
        'el' => ['Cyprus Blockchain Week', 'Τριήμερο ομιλιών για tokenisation, συμμόρφωση με τον MiCA και χρηματοοικονομικά σε blockchain.']],
    ['when' => 'later', 'days' => 42, 'hour' => 10, 'hours' => 5, 'city' => 'Troodos', 'venue' => 'Mountain Research Farm', 'industries' => ['agritech', 'foodtech'], 'price' => ['en' => '€20', 'el' => '€20'],
        'en' => ['AgriTech Field Day Troodos', 'See sensor-driven irrigation and drone crop monitoring in action at a working vineyard.'],
        'el' => ['Ημέρα Αγροτεχνολογίας στο Τρόοδος', 'Δείτε στην πράξη άρδευση με αισθητήρες και παρακολούθηση καλλιεργειών με drones σε αμπελώνα.']],
    ['when' => 'later', 'days' => 48, 'hour' => 15, 'hours' => 3, 'online' => true, 'industries' => ['funding-venture-capital', 'crowdfunding'], 'price' => ['en' => 'Free, by application', 'el' => 'Δωρεάν, με αίτηση'],
        'en' => ['Investor Office Hours', 'Book a 20-minute slot with partners from regional venture funds.'],
        'el' => ['Ώρες Γραφείου με Επενδυτές', 'Κλείστε 20λεπτη συνάντηση με συνεταίρους περιφερειακών venture funds.']],
    ['when' => 'later', 'days' => 60, 'hour' => 9, 'hours' => 8, 'city' => 'Nicosia', 'venue' => 'University Auditorium', 'industries' => ['space', 'quantum'], 'featured' => true, 'price' => ['en' => '€40', 'el' => '€40'],
        'en' => ['Space Tech Cyprus Conference', 'Ground stations, Earth observation and satellite startups from across the region.'],
        'el' => ['Συνέδριο Space Tech Cyprus', 'Επίγειοι σταθμοί, παρατήρηση Γης και δορυφορικές startups από όλη την περιοχή.']],
    ['when' => 'later', 'days' => 70, 'hour' => 10, 'hours' => 4, 'city' => 'Nicosia', 'venue' => 'Digital Government Lab', 'industries' => ['govtech', 'civictech'], 'price' => ['en' => 'Free', 'el' => 'Δωρεάν'],
        'en' => ['GovTech Open Day', 'Public-sector teams demo new digital services and invite startups to upcoming tenders.'],
        'el' => ['Ανοικτή Ημέρα GovTech', 'Ομάδες του δημοσίου παρουσιάζουν νέες ψηφιακές υπηρεσίες και ενημερώνουν startups για επερχόμενους διαγωνισμούς.']],
];
