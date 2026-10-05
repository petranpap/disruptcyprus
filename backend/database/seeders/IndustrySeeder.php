<?php

namespace Database\Seeders;

use App\Enums\IndustryGroup;
use App\Models\Industry;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    /**
     * Order follows the product brief. Greek names are drafts for editorial review:
     * established English terms (FinTech, SaaS, Blockchain…) are kept as Greek tech media use them.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: IndustryGroup}>
     */
    public const INDUSTRIES = [
        ['fintech', 'FinTech', 'FinTech', IndustryGroup::FinanceInvestment],
        ['blockchain', 'Blockchain', 'Blockchain', IndustryGroup::FinanceInvestment],
        ['healthtech', 'HealthTech', 'Τεχνολογία Υγείας', IndustryGroup::Sectors],
        ['biotech', 'BioTech', 'Βιοτεχνολογία', IndustryGroup::DeepTech],
        ['cleantech', 'CleanTech', 'Πράσινη Τεχνολογία', IndustryGroup::Sectors],
        ['b2c-saas', 'B2C SaaS', 'B2C SaaS', IndustryGroup::DigitalSoftware],
        ['b2b-saas', 'B2B SaaS', 'B2B SaaS', IndustryGroup::DigitalSoftware],
        ['cybersecurity', 'Cybersecurity', 'Κυβερνοασφάλεια', IndustryGroup::DeepTech],
        ['robotics', 'Robotics', 'Ρομποτική', IndustryGroup::DeepTech],
        ['iot', 'IoT', 'Διαδίκτυο των Πραγμάτων (IoT)', IndustryGroup::DeepTech],
        ['mobility', 'Mobility', 'Κινητικότητα', IndustryGroup::Sectors],
        ['media', 'Media', 'Μέσα Ενημέρωσης', IndustryGroup::DigitalSoftware],
        ['gaming', 'Gaming', 'Gaming', IndustryGroup::DigitalSoftware],
        ['vr-mr-ar', 'VR/MR/AR', 'Εικονική & Επαυξημένη Πραγματικότητα', IndustryGroup::DeepTech],
        ['edtech', 'EdTech', 'Εκπαιδευτική Τεχνολογία', IndustryGroup::Sectors],
        ['social-impact', 'Social Impact', 'Κοινωνικός Αντίκτυπος', IndustryGroup::SocietyGov],
        ['maritime', 'Maritime', 'Ναυτιλία', IndustryGroup::Sectors],
        ['foodtech', 'FoodTech', 'Τεχνολογία Τροφίμων', IndustryGroup::Sectors],
        ['agritech', 'AgriTech', 'Αγροτεχνολογία', IndustryGroup::Sectors],
        ['proptech', 'PropTech', 'Τεχνολογία Ακινήτων', IndustryGroup::FinanceInvestment],
        ['work-productivity', 'Work & Productivity', 'Εργασία & Παραγωγικότητα', IndustryGroup::DigitalSoftware],
        ['enterprise-ict', 'Enterprise ICT', 'Επιχειρησιακές ΤΠΕ', IndustryGroup::DigitalSoftware],
        ['cloud', 'Cloud', 'Υπολογιστικό Νέφος', IndustryGroup::DigitalSoftware],
        ['traveltech', 'TravelTech', 'Τεχνολογία Τουρισμού', IndustryGroup::Sectors],
        ['space', 'Space', 'Διάστημα', IndustryGroup::DeepTech],
        ['quantum', 'Quantum', 'Κβαντικές Τεχνολογίες', IndustryGroup::DeepTech],
        ['smart-city', 'Smart City', 'Έξυπνες Πόλεις', IndustryGroup::SocietyGov],
        ['govtech', 'GovTech', 'Ψηφιακή Διακυβέρνηση', IndustryGroup::SocietyGov],
        ['civictech', 'CivicTech', 'Τεχνολογία για τον Πολίτη', IndustryGroup::SocietyGov],
        ['martech', 'MarTech', 'Τεχνολογία Μάρκετινγκ', IndustryGroup::DigitalSoftware],
        ['crowdfunding', 'Crowdfunding', 'Πληθοχρηματοδότηση', IndustryGroup::FinanceInvestment],
        ['artificial-intelligence', 'Artificial Intelligence', 'Τεχνητή Νοημοσύνη', IndustryGroup::DeepTech],
        ['funding-venture-capital', 'Funding & Venture Capital', 'Χρηματοδότηση & Venture Capital', IndustryGroup::FinanceInvestment],
    ];

    /** Accent stripe colors, rotated across industries. */
    private const COLORS = ['#00A5E6', '#E21E49', '#7C4DFF', '#00A884', '#F59E0B', '#0EA5E9', '#EC4899', '#10B981', '#6366F1', '#F97316', '#14B8A6', '#8B5CF6'];

    public function run(): void
    {
        foreach (self::INDUSTRIES as $index => [$slug, $nameEn, $nameEl, $group]) {
            Industry::query()->updateOrCreate(['slug' => $slug], [
                'name' => ['en' => $nameEn, 'el' => $nameEl],
                'group' => $group,
                'color' => self::COLORS[$index % count(self::COLORS)],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }
    }
}
