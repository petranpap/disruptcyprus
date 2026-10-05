<?php

/**
 * Demo articles. All companies, funds and people are fictional.
 *
 * Keys: section, industries (first = primary), author (index into AuthorSeeder), hours (age),
 * original, featured, en/el => [title, excerpt] (missing locale = single-language article).
 */
return [
    // ── News ──────────────────────────────────────────────────────────────
    [
        'section' => 'news', 'industries' => ['funding-venture-capital', 'fintech'], 'author' => 0, 'hours' => 2, 'original' => true, 'featured' => true,
        'en' => ['Cyprus records €450M in tech venture inflows as the ecosystem matures', 'International funds and homegrown founders pushed venture investment to a new high in the first nine months of the year, led by fintech and AI rounds.'],
        'el' => ['Ρεκόρ €450 εκατ. σε επενδύσεις venture στην κυπριακή τεχνολογία', 'Διεθνή κεφάλαια και Κύπριοι ιδρυτές οδήγησαν τις επενδύσεις venture σε νέο υψηλό το πρώτο εννεάμηνο, με πρωταγωνιστές τα fintech και την τεχνητή νοημοσύνη.'],
    ],
    [
        'section' => 'news', 'industries' => ['artificial-intelligence', 'quantum'], 'author' => 4, 'hours' => 5,
        'en' => ['National innovation agency opens €30M call for deep-tech pilots', 'The new programme funds pilot projects in AI, quantum and advanced materials, with grants of up to €750,000 per consortium.'],
        'el' => ['Νέα πρόσκληση €30 εκατ. για πιλοτικά έργα βαθιάς τεχνολογίας', 'Το πρόγραμμα χρηματοδοτεί πιλοτικά έργα σε τεχνητή νοημοσύνη, κβαντικές τεχνολογίες και προηγμένα υλικά, με επιχορηγήσεις έως €750.000 ανά κοινοπραξία.'],
    ],
    [
        'section' => 'news', 'industries' => ['maritime', 'robotics'], 'author' => 5, 'hours' => 9,
        'en' => ['Limassol port pilots fully electric autonomous cranes', 'A six-month trial will measure whether automated electric cranes can cut container turnaround times and emissions at the island’s busiest port.'],
        'el' => ['Πιλοτική λειτουργία αυτόνομων ηλεκτρικών γερανών στο λιμάνι Λεμεσού', 'Εξάμηνη δοκιμή θα δείξει αν οι αυτοματοποιημένοι ηλεκτρικοί γερανοί μειώνουν τον χρόνο διακίνησης φορτίων και τις εκπομπές στο μεγαλύτερο λιμάνι του νησιού.'],
    ],
    [
        'section' => 'news', 'industries' => ['smart-city', 'mobility', 'govtech'], 'author' => 4, 'hours' => 14,
        'en' => ['Nicosia launches an open-data portal for smart mobility', 'Real-time bus positions, parking occupancy and cycling counts are now available through a public API for developers and researchers.'],
        'el' => ['Η Λευκωσία ανοίγει πύλη ανοικτών δεδομένων για την έξυπνη κινητικότητα', 'Θέσεις λεωφορείων σε πραγματικό χρόνο, πληρότητα χώρων στάθμευσης και μετρήσεις ποδηλάτων διατίθενται πλέον μέσω δημόσιου API.'],
    ],
    [
        'section' => 'news', 'industries' => ['govtech', 'work-productivity'], 'author' => 4, 'hours' => 26,
        'en' => ['Digital nomad visa cap raised by 35% after strong demand', 'The government expanded the quota for remote tech workers, citing a growing pool of engineers relocating to Limassol and Larnaca.'],
        'el' => ['Αύξηση 35% στο όριο της βίζας ψηφιακών νομάδων', 'Η κυβέρνηση διεύρυνε την ποσόστωση για απομακρυσμένους εργαζόμενους τεχνολογίας, λόγω της αυξανόμενης μετεγκατάστασης μηχανικών σε Λεμεσό και Λάρνακα.'],
    ],
    [
        'section' => 'news', 'industries' => ['cybersecurity', 'enterprise-ict'], 'author' => 5, 'hours' => 31,
        'en' => ['Cybersecurity authority warns SMEs of a surge in invoice fraud', 'Attackers are impersonating suppliers with lookalike domains; the authority recommends payment call-backs and mandatory multi-factor sign-in.'],
        'el' => ['Προειδοποίηση για αύξηση της απάτης με τιμολόγια σε μικρομεσαίες επιχειρήσεις', 'Οι επιτιθέμενοι παριστάνουν προμηθευτές με παρόμοια domains· συστήνεται τηλεφωνική επιβεβαίωση πληρωμών και υποχρεωτικός έλεγχος ταυτότητας πολλαπλών παραγόντων.'],
    ],
    [
        'section' => 'news', 'industries' => ['cleantech'], 'author' => 0, 'hours' => 40, 'featured' => true,
        'en' => ['Solar-plus-storage tender to power 80,000 homes in Paphos', 'Three consortia are shortlisted for a 120 MW solar park with battery storage designed to ease evening peaks on the isolated grid.'],
        'el' => ['Διαγωνισμός για φωτοβολταϊκά με αποθήκευση που θα τροφοδοτούν 80.000 σπίτια στην Πάφο', 'Τρεις κοινοπραξίες προκρίθηκαν για φωτοβολταϊκό πάρκο 120 MW με μπαταρίες, σχεδιασμένο να εξομαλύνει τις βραδινές αιχμές του απομονωμένου δικτύου.'],
    ],
    [
        'section' => 'news', 'industries' => ['fintech', 'blockchain'], 'author' => 1, 'hours' => 52,
        'en' => ['EU regulatory sandbox admits 12 Cypriot fintechs', 'The cohort will test cross-border instant payments and tokenised settlement under supervised conditions for up to 18 months.'],
        'el' => ['Δώδεκα κυπριακές fintech εντάσσονται σε ευρωπαϊκό ρυθμιστικό sandbox', 'Οι εταιρείες θα δοκιμάσουν διασυνοριακές άμεσες πληρωμές και εκκαθάριση με ψηφιακά tokens υπό εποπτεία, για έως 18 μήνες.'],
    ],
    [
        'section' => 'news', 'industries' => ['traveltech', 'artificial-intelligence'], 'author' => 5, 'hours' => 75,
        'en' => ['Larnaca airport trials biometric boarding with a local startup', 'Passengers on selected flights can board using a face scan instead of a boarding pass, with images deleted within an hour of departure.'],
    ],
    [
        'section' => 'news', 'industries' => ['agritech', 'iot', 'foodtech'], 'author' => 2, 'hours' => 98,
        'el' => ['Πιλοτικό αγροτεχνολογίας μειώνει 30% τη χρήση νερού σε αμπελώνες του Τροόδους', 'Αισθητήρες υγρασίας εδάφους και προβλέψεις καιρού καθοδηγούν το πότισμα σε 40 αμπελώνες, με σταθερή απόδοση και ποιότητα σταφυλιών.'],
    ],
    [
        'section' => 'news', 'industries' => ['healthtech', 'govtech'], 'author' => 4, 'hours' => 130,
        'en' => ['New e-health record app reaches 900,000 patients', 'Patients can now view prescriptions, lab results and referrals on their phones, and share records with doctors using a one-time code.'],
        'el' => ['Νέα εφαρμογή ηλεκτρονικού φακέλου υγείας για 900.000 ασθενείς', 'Οι ασθενείς βλέπουν συνταγές, εξετάσεις και παραπεμπτικά στο κινητό και μοιράζονται τον φάκελό τους με γιατρούς μέσω κωδικού μίας χρήσης.'],
    ],
    [
        'section' => 'news', 'industries' => ['quantum', 'cybersecurity'], 'author' => 2, 'hours' => 190,
        'en' => ['Cyprus joins the European quantum communication network', 'A fibre link between Nicosia and Limassol will carry quantum-key-distribution traffic as part of the EU-wide secure infrastructure.'],
        'el' => ['Η Κύπρος εντάσσεται στο ευρωπαϊκό δίκτυο κβαντικών επικοινωνιών', 'Οπτική ζεύξη Λευκωσίας–Λεμεσού θα μεταφέρει κβαντική διανομή κλειδιών στο πλαίσιο της πανευρωπαϊκής ασφαλούς υποδομής.'],
    ],
    [
        'section' => 'news', 'industries' => ['cloud', 'enterprise-ict'], 'author' => 0, 'hours' => 260,
        'en' => ['Cloud provider opens an edge zone in Nicosia', 'Local hosting cuts latency for banks, gaming studios and public services that must keep data inside the EU.'],
        'el' => ['Πάροχος cloud ανοίγει edge zone στη Λευκωσία', 'Η τοπική φιλοξενία μειώνει την καθυστέρηση για τράπεζες, studios παιχνιδιών και δημόσιες υπηρεσίες που πρέπει να κρατούν δεδομένα εντός ΕΕ.'],
    ],
    [
        'section' => 'news', 'industries' => ['gaming', 'media'], 'author' => 5, 'hours' => 330,
        'en' => ['Esports arena opens at Limassol marina', 'The 600-seat venue will host regional tournaments and give local studios a space to showcase games to players and publishers.'],
    ],

    // ── Startups ──────────────────────────────────────────────────────────
    [
        'section' => 'startups', 'industries' => ['artificial-intelligence', 'maritime', 'funding-venture-capital'], 'author' => 3, 'hours' => 4, 'featured' => true,
        'en' => ['Wavelength AI raises €6M seed to optimise shipping routes', 'The Limassol startup uses weather and port-congestion data to cut fuel use on bulk carriers; the round was led by two European deep-tech funds.'],
        'el' => ['Η Wavelength AI αντλεί €6 εκατ. για βελτιστοποίηση θαλάσσιων διαδρομών', 'Η startup της Λεμεσού αξιοποιεί δεδομένα καιρού και συμφόρησης λιμανιών για μείωση καυσίμων σε φορτηγά πλοία· ηγήθηκαν δύο ευρωπαϊκά deep-tech funds.'],
    ],
    [
        'section' => 'startups', 'industries' => ['b2b-saas', 'work-productivity'], 'author' => 3, 'hours' => 20, 'original' => true,
        'en' => ['From a Nicosia spare room to 40 countries: the Teamloop story', 'Two former consultants built an HR scheduling tool for shift workers. Five years later it pays 60 salaries in Cyprus and serves 3,000 companies.'],
        'el' => ['Από ένα δωμάτιο στη Λευκωσία σε 40 χώρες: η ιστορία της Teamloop', 'Δύο πρώην σύμβουλοι έφτιαξαν εργαλείο προγραμματισμού βαρδιών. Πέντε χρόνια μετά, απασχολεί 60 άτομα στην Κύπρο και εξυπηρετεί 3.000 εταιρείες.'],
    ],
    [
        'section' => 'startups', 'industries' => ['foodtech'], 'author' => 5, 'hours' => 44,
        'en' => ['Halloumi Labs develops a plant-based halloumi that grills', 'The startup’s fermented chickpea formula keeps its shape on the grill; first retail tests start in Cyprus and Greece this winter.'],
        'el' => ['Η Halloumi Labs αναπτύσσει φυτικό χαλλούμι που ψήνεται', 'Η συνταγή με ζυμωμένα ρεβίθια διατηρεί το σχήμα της στη σχάρα· οι πρώτες δοκιμές λιανικής ξεκινούν σε Κύπρο και Ελλάδα τον χειμώνα.'],
    ],
    [
        'section' => 'startups', 'industries' => ['edtech', 'b2c-saas'], 'author' => 3, 'hours' => 70,
        'en' => ['EdTech app Mathsy passes 100,000 Greek-speaking students', 'Adaptive exercises aligned with the Cyprus and Greek curricula helped the app grow mostly through teacher recommendations.'],
        'el' => ['Η εφαρμογή Mathsy ξεπερνά τους 100.000 ελληνόφωνους μαθητές', 'Προσαρμοστικές ασκήσεις ευθυγραμμισμένες με τα αναλυτικά προγράμματα Κύπρου και Ελλάδας έφεραν ανάπτυξη κυρίως μέσω συστάσεων εκπαιδευτικών.'],
    ],
    [
        'section' => 'startups', 'industries' => ['proptech', 'fintech'], 'author' => 1, 'hours' => 110,
        'en' => ['Keyhaven wants to end disputes over rental deposits', 'The PropTech startup holds deposits in escrow and uses check-in and check-out photo reports to settle claims within days.'],
        'el' => ['Η Keyhaven θέλει να τελειώσει τις διαφορές για τις εγγυήσεις ενοικίων', 'Η startup κρατά τις εγγυήσεις σε μεσεγγύηση και χρησιμοποιεί φωτογραφικές αναφορές παράδοσης για επίλυση διαφορών μέσα σε λίγες μέρες.'],
    ],
    [
        'section' => 'startups', 'industries' => ['healthtech', 'iot'], 'author' => 2, 'hours' => 150,
        'en' => ['PulseCare wearable receives CE mark for heart-rhythm monitoring', 'The patch records two weeks of ECG data and flags irregular rhythms to cardiologists, replacing bulky Holter monitors.'],
        'el' => ['Σήμανση CE για το wearable της PulseCare για παρακολούθηση καρδιακού ρυθμού', 'Το αυτοκόλλητο καταγράφει ΗΚΓ δύο εβδομάδων και ειδοποιεί καρδιολόγους για αρρυθμίες, αντικαθιστώντας τα ογκώδη Holter.'],
    ],
    [
        'section' => 'startups', 'industries' => ['social-impact', 'work-productivity'], 'author' => 4, 'hours' => 210,
        'el' => ['Startup κοινωνικού αντίκτυπου συνδέει πρόσφυγες με θέσεις εργασίας στην τεχνολογία', 'Η πλατφόρμα συνδυάζει εκπαίδευση σε QA και υποστήριξη πελατών με πρακτική άσκηση σε τοπικές εταιρείες λογισμικού.'],
    ],
    [
        'section' => 'startups', 'industries' => ['vr-mr-ar', 'traveltech', 'media'], 'author' => 5, 'hours' => 280,
        'en' => ['Odyssey XR rebuilds ancient Kourion in mixed reality', 'Visitors can see the theatre and villas as they stood in the 4th century, using headsets or their phones on site.'],
        'el' => ['Η Odyssey XR αναπαριστά το αρχαίο Κούριο σε μικτή πραγματικότητα', 'Οι επισκέπτες βλέπουν το θέατρο και τις επαύλεις όπως ήταν τον 4ο αιώνα, με γυαλιά ή με το κινητό τους στον χώρο.'],
    ],
    [
        'section' => 'startups', 'industries' => ['martech', 'b2b-saas'], 'author' => 1, 'hours' => 400,
        'en' => ['MarTech startup Brandwise acquired by a German marketing group', 'The deal keeps the 25-person engineering team in Limassol and makes it the group’s centre for AI-driven campaign tools.'],
        'el' => ['Γερμανικός όμιλος μάρκετινγκ εξαγοράζει τη Brandwise', 'Η συμφωνία διατηρεί την ομάδα 25 μηχανικών στη Λεμεσό ως κέντρο του ομίλου για εργαλεία καμπανιών με τεχνητή νοημοσύνη.'],
    ],
    [
        'section' => 'startups', 'industries' => ['robotics', 'smart-city'], 'author' => 3, 'hours' => 520,
        'en' => ['Drone inspection startup SkyPatrol expands to Greece', 'Its autonomous drones inspect power lines and rooftops; the Athens office opens with contracts from two utilities.'],
        'el' => ['Η SkyPatrol επεκτείνεται στην Ελλάδα', 'Τα αυτόνομα drones της επιθεωρούν γραμμές ηλεκτροδότησης και στέγες· το γραφείο της Αθήνας ανοίγει με συμβόλαια από δύο παρόχους.'],
    ],

    // ── Research ──────────────────────────────────────────────────────────
    [
        'section' => 'research', 'industries' => ['artificial-intelligence', 'media'], 'author' => 2, 'hours' => 7, 'original' => true, 'featured' => true,
        'en' => ['University lab trains the first language model on Cypriot Greek', 'Researchers collected 2 billion words of dialect text and speech transcripts to build an open model for local public services.'],
        'el' => ['Πανεπιστημιακό εργαστήριο εκπαιδεύει το πρώτο γλωσσικό μοντέλο στην κυπριακή διάλεκτο', 'Οι ερευνητές συγκέντρωσαν 2 δισ. λέξεις διαλεκτικού κειμένου και απομαγνητοφωνήσεων για ένα ανοικτό μοντέλο για δημόσιες υπηρεσίες.'],
    ],
    [
        'section' => 'research', 'industries' => ['space'], 'author' => 2, 'hours' => 36,
        'en' => ['Study maps Cyprus as a ground-station hub for satellite constellations', 'Clear skies, latitude and subsea cables make the island a strong candidate for low-earth-orbit ground stations, the report finds.'],
        'el' => ['Μελέτη: η Κύπρος ως κόμβος επίγειων σταθμών δορυφόρων', 'Ο καθαρός ουρανός, το γεωγραφικό πλάτος και τα υποθαλάσσια καλώδια κάνουν το νησί ισχυρό υποψήφιο για σταθμούς χαμηλής τροχιάς.'],
    ],
    [
        'section' => 'research', 'industries' => ['biotech', 'healthtech'], 'author' => 2, 'hours' => 88,
        'en' => ['Gene-editing study targets thalassaemia at a Nicosia institute', 'Early lab results show corrected blood stem cells producing healthy haemoglobin; clinical trials are at least three years away.'],
        'el' => ['Μελέτη γονιδιακής επεξεργασίας για τη θαλασσαιμία σε ινστιτούτο της Λευκωσίας', 'Τα πρώτα εργαστηριακά αποτελέσματα δείχνουν διορθωμένα βλαστοκύτταρα να παράγουν υγιή αιμοσφαιρίνη· οι κλινικές δοκιμές απέχουν τουλάχιστον τρία χρόνια.'],
    ],
    [
        'section' => 'research', 'industries' => ['funding-venture-capital', 'social-impact'], 'author' => 0, 'hours' => 120, 'original' => true, 'featured' => true, 'attachment' => true,
        'en' => ['State of the Cyprus Startup Ecosystem 2026', 'Our annual report covers 420 startups, funding by stage and sector, hiring trends and what founders want from policymakers. Download the full PDF.'],
        'el' => ['Η κατάσταση του κυπριακού οικοσυστήματος startups 2026', 'Η ετήσια έκθεσή μας καλύπτει 420 startups, χρηματοδότηση ανά στάδιο και κλάδο, τάσεις προσλήψεων και τι ζητούν οι ιδρυτές. Κατεβάστε το πλήρες PDF.'],
    ],
    [
        'section' => 'research', 'industries' => ['cleantech'], 'author' => 2, 'hours' => 230,
        'en' => ['Solar membrane desalination doubles output in lab tests', 'A new membrane coating heated directly by sunlight produced twice as much fresh water per square metre as current solar stills.'],
        'el' => ['Ηλιακές μεμβράνες αφαλάτωσης διπλασιάζουν την απόδοση σε εργαστηριακές δοκιμές', 'Νέα επίστρωση μεμβράνης που θερμαίνεται απευθείας από τον ήλιο παρήγαγε διπλάσιο γλυκό νερό ανά τετραγωνικό μέτρο.'],
    ],
    [
        'section' => 'research', 'industries' => ['iot', 'smart-city', 'cleantech'], 'author' => 5, 'hours' => 300,
        'en' => ['Smart-grid trial in Larnaca cuts peak demand by 12%', 'Connected water heaters and EV chargers shifted load away from evening peaks without households noticing, the final report shows.'],
        'el' => ['Πιλοτικό έξυπνου δικτύου στη Λάρνακα μειώνει τη ζήτηση αιχμής κατά 12%', 'Συνδεδεμένοι θερμοσίφωνες και φορτιστές ηλεκτρικών οχημάτων μετατόπισαν το φορτίο από τις βραδινές αιχμές χωρίς να το αντιληφθούν τα νοικοκυριά.'],
    ],
    [
        'section' => 'research', 'industries' => ['quantum', 'cybersecurity'], 'author' => 2, 'hours' => 450,
        'en' => ['Local researchers benchmark quantum-safe encryption on low-power devices', 'The study shows which post-quantum algorithms run fast enough on smart meters and medical sensors.'],
        'el' => ['Κύπριοι ερευνητές αξιολογούν κρυπτογράφηση ανθεκτική σε κβαντικούς υπολογιστές', 'Η μελέτη δείχνει ποιοι μετα-κβαντικοί αλγόριθμοι τρέχουν αρκετά γρήγορα σε έξυπνους μετρητές και ιατρικούς αισθητήρες.'],
    ],
    [
        'section' => 'research', 'industries' => ['work-productivity', 'enterprise-ict'], 'author' => 5, 'hours' => 600,
        'en' => ['Survey: hybrid work is the default at 7 in 10 Cyprus tech firms', 'Companies report stable productivity but say onboarding junior engineers remotely remains their biggest challenge.'],
    ],

    // ── Investors ─────────────────────────────────────────────────────────
    [
        'section' => 'investors', 'industries' => ['funding-venture-capital'], 'author' => 1, 'hours' => 11, 'original' => true, 'featured' => true,
        'en' => ['Levantine Horizon Ventures closes €80M second fund', 'The fund will back 25 seed and Series A companies across Cyprus, Greece and Israel, with a focus on B2B software and climate tech.'],
        'el' => ['Η Levantine Horizon Ventures ολοκληρώνει δεύτερο fund €80 εκατ.', 'Το fund θα στηρίξει 25 εταιρείες seed και Series A σε Κύπρο, Ελλάδα και Ισραήλ, με έμφαση στο B2B λογισμικό και την κλιματική τεχνολογία.'],
    ],
    [
        'section' => 'investors', 'industries' => ['funding-venture-capital', 'crowdfunding'], 'author' => 1, 'hours' => 60,
        'en' => ['Angel network reports a record number of deals in 2026', 'Members made 48 investments so far this year, with the average ticket rising to €45,000 as more founders return as angels.'],
        'el' => ['Ρεκόρ συμφωνιών για το δίκτυο angel επενδυτών το 2026', 'Τα μέλη πραγματοποίησαν 48 επενδύσεις φέτος, με το μέσο ποσό να ανεβαίνει στις €45.000 καθώς περισσότεροι ιδρυτές γίνονται angels.'],
    ],
    [
        'section' => 'investors', 'industries' => ['funding-venture-capital'], 'author' => 1, 'hours' => 140, 'original' => true,
        'en' => ['Why regional funds are opening offices in Limassol', 'Partners at three venture firms explain what draws them to Cyprus: talent, time zone, EU access and a growing pipeline of repeat founders.'],
        'el' => ['Γιατί περιφερειακά funds ανοίγουν γραφεία στη Λεμεσό', 'Συνέταιροι τριών εταιρειών venture εξηγούν τι τους φέρνει στην Κύπρο: ταλέντο, ζώνη ώρας, πρόσβαση στην ΕΕ και ιδρυτές που ξαναδοκιμάζουν.'],
    ],
    [
        'section' => 'investors', 'industries' => ['crowdfunding', 'fintech'], 'author' => 1, 'hours' => 240,
        'en' => ['What the EU crowdfunding licence means for Cypriot platforms', 'A single licence now lets platforms raise from investors across the EU, but it brings stricter disclosure and investor-protection rules.'],
        'el' => ['Τι σημαίνει η ευρωπαϊκή άδεια πληθοχρηματοδότησης για τις κυπριακές πλατφόρμες', 'Μία άδεια επιτρέπει πλέον άντληση κεφαλαίων από επενδυτές σε όλη την ΕΕ, με αυστηρότερους κανόνες γνωστοποίησης και προστασίας επενδυτών.'],
    ],
    [
        'section' => 'investors', 'industries' => ['blockchain', 'proptech'], 'author' => 1, 'hours' => 350,
        'en' => ['Tokenised real-estate funds: how the regulator sees them', 'The supervisor clarified when property tokens count as securities and what investor protections apply to retail buyers.'],
        'el' => ['Ψηφιακά tokens ακινήτων: η θέση της εποπτικής αρχής', 'Η αρχή διευκρίνισε πότε τα tokens ακινήτων θεωρούνται κινητές αξίες και ποιες προστασίες ισχύουν για ιδιώτες επενδυτές.'],
    ],
    [
        'section' => 'investors', 'industries' => ['maritime', 'funding-venture-capital'], 'author' => 1, 'hours' => 480,
        'en' => ['Shipping groups turn venture investors in maritime tech', 'Three Limassol ship managers launched corporate venture arms to back decarbonisation and crew-welfare startups.'],
        'el' => ['Ναυτιλιακοί όμιλοι επενδύουν σε startups ναυτιλιακής τεχνολογίας', 'Τρεις διαχειρίστριες πλοίων της Λεμεσού δημιούργησαν εταιρικά venture για startups απανθρακοποίησης και ευημερίας πληρωμάτων.'],
    ],
    [
        'section' => 'investors', 'industries' => ['funding-venture-capital', 'b2b-saas'], 'author' => 1, 'hours' => 560,
        'en' => ['Seed valuations in the Eastern Mediterranean: 2026 benchmarks', 'Median pre-money valuations rose 8% year on year, with B2B SaaS rounds pricing above consumer and marketplace deals.'],
        'el' => ['Αποτιμήσεις seed στην Ανατολική Μεσόγειο: τα δεδομένα του 2026', 'Η διάμεση αποτίμηση pre-money αυξήθηκε 8% σε ετήσια βάση, με τους γύρους B2B SaaS πάνω από τις καταναλωτικές πλατφόρμες.'],
    ],
    [
        'section' => 'investors', 'industries' => ['cleantech', 'agritech', 'social-impact'], 'author' => 1, 'hours' => 680,
        'en' => ['New impact fund targets climate solutions for island economies', 'The €40M vehicle will invest in water, energy and agritech startups solving problems common to Mediterranean islands.'],
        'el' => ['Νέο fund επενδύσεων αντίκτυπου για κλιματικές λύσεις σε νησιωτικές οικονομίες', 'Το όχημα των €40 εκατ. θα επενδύσει σε startups νερού, ενέργειας και αγροτεχνολογίας για προβλήματα των νησιών της Μεσογείου.'],
    ],
];
