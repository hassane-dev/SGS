<?php

require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/../core/bootstrap_i18n.php';

class BulletinI18nHelper {

    /**
     * Standard dictionary mapping canonical msgids to translations across supported languages.
     * Guarantees exact translations even if gettext system locales are partially uninstalled in OS sandbox.
     */
    private static array $dictionary = [
        'BULLETIN SCOLAIRE' => [
            'fr_FR' => 'BULLETIN SCOLAIRE',
            'en_US' => 'SCHOOL REPORT',
            'ar'    => 'بطاقة تقرير مدرسي'
        ],
        'BULLETIN PROVISOIRE' => [
            'fr_FR' => 'BULLETIN PROVISOIRE',
            'en_US' => 'PROVISIONAL REPORT',
            'ar'    => 'تقرير مؤقت'
        ],
        'BULLETIN OFFICIEL' => [
            'fr_FR' => 'BULLETIN OFFICIEL',
            'en_US' => 'OFFICIAL REPORT',
            'ar'    => 'تقرير رسمي'
        ],
        'Année Académique' => [
            'fr_FR' => 'Année Académique',
            'en_US' => 'Academic Year',
            'ar'    => 'العام الدراسي'
        ],
        'Bulletin de la Séquence' => [
            'fr_FR' => 'Bulletin de la Séquence',
            'en_US' => 'Sequence Report',
            'ar'    => 'تقرير الدورة'
        ],
        'Séquence' => [
            'fr_FR' => 'Séquence',
            'en_US' => 'Sequence',
            'ar'    => 'الفصل / الدورة'
        ],
        'Matricule' => [
            'fr_FR' => 'Matricule',
            'en_US' => 'Student ID',
            'ar'    => 'الرقم التسلسلي'
        ],
        'Nom & Prénom' => [
            'fr_FR' => 'Nom & Prénom',
            'en_US' => 'Full Name',
            'ar'    => 'الاسم واللقب'
        ],
        'Élève' => [
            'fr_FR' => 'Élève',
            'en_US' => 'Student',
            'ar'    => 'الطالب'
        ],
        'Date de Naissance' => [
            'fr_FR' => 'Date de Naissance',
            'en_US' => 'Date of Birth',
            'ar'    => 'تاريخ الميلاد'
        ],
        'Classe' => [
            'fr_FR' => 'Classe',
            'en_US' => 'Class',
            'ar'    => 'الفصل'
        ],
        'Matières' => [
            'fr_FR' => 'Matières',
            'en_US' => 'Subjects',
            'ar'    => 'المواد'
        ],
        'Moyenne' => [
            'fr_FR' => 'Moyenne',
            'en_US' => 'Average',
            'ar'    => 'المعدل'
        ],
        'Moyenne / 20' => [
            'fr_FR' => 'Moyenne / 20',
            'en_US' => 'Average / 20',
            'ar'    => 'المعدل / 20'
        ],
        'Coef' => [
            'fr_FR' => 'Coef',
            'en_US' => 'Coef',
            'ar'    => 'المعامل'
        ],
        'Coefficient' => [
            'fr_FR' => 'Coefficient',
            'en_US' => 'Coefficient',
            'ar'    => 'المعامل'
        ],
        'Total Points' => [
            'fr_FR' => 'Total Points',
            'en_US' => 'Total Points',
            'ar'    => 'مجموع النقاط'
        ],
        "Appreciations de l'enseignant" => [
            'fr_FR' => "Appréciations de l'enseignant",
            'en_US' => "Teacher Appraisals",
            'ar'    => "ملاحظات المعلم"
        ],
        'Totaux' => [
            'fr_FR' => 'Totaux',
            'en_US' => 'Totals',
            'ar'    => 'المجاميع'
        ],
        'Moyenne Générale' => [
            'fr_FR' => 'Moyenne Générale',
            'en_US' => 'General Average',
            'ar'    => 'المعدل العام'
        ],
        'Rang' => [
            'fr_FR' => 'Rang',
            'en_US' => 'Rank',
            'ar'    => 'الترتيب'
        ],
        'Statut du bulletin' => [
            'fr_FR' => 'Statut du bulletin',
            'en_US' => 'Report Status',
            'ar'    => 'حالة التقرير'
        ],
        'Appréciation du Conseil de Classe' => [
            'fr_FR' => 'Appréciation du Conseil de Classe',
            'en_US' => 'Class Council Assessment',
            'ar'    => 'ملاحظات مجلس الفصل'
        ],
        'Appréciation Générale' => [
            'fr_FR' => 'Appréciation Générale',
            'en_US' => 'General Assessment',
            'ar'    => 'التقييم العام'
        ],
        'DISTINCTION / PALMARÈS' => [
            'fr_FR' => 'DISTINCTION / PALMARÈS',
            'en_US' => 'HONORS & AWARDS',
            'ar'    => 'شرفيات وجوائز'
        ],
        'Distinction' => [
            'fr_FR' => 'Distinction',
            'en_US' => 'Honor / Distinction',
            'ar'    => 'درجة الشرف'
        ],
        "Tableau d'honneur + Félicitations" => [
            'fr_FR' => "Tableau d'honneur + Félicitations",
            'en_US' => 'Honor Roll + Congratulations',
            'ar'    => 'لوحة الشرف + تهنئة'
        ],
        "Tableau d'honneur + Encouragements" => [
            'fr_FR' => "Tableau d'honneur + Encouragements",
            'en_US' => 'Honor Roll + Encouragements',
            'ar'    => 'لوحة الشرف + تشجيع'
        ],
        "Tableau d'honneur" => [
            'fr_FR' => "Tableau d'honneur",
            'en_US' => 'Honor Roll',
            'ar'    => 'لوحة الشرف'
        ],
        'Encouragements' => [
            'fr_FR' => 'Encouragements',
            'en_US' => 'Encouragements',
            'ar'    => 'تشجيع'
        ],
        'Félicitations' => [
            'fr_FR' => 'Félicitations',
            'en_US' => 'Congratulations',
            'ar'    => 'تهنئة'
        ],
        'Aucune distinction' => [
            'fr_FR' => 'Aucune distinction',
            'en_US' => 'No distinction',
            'ar'    => 'بدون درجة شرف'
        ],
        'Français' => ['fr_FR' => 'Français', 'en_US' => 'French', 'ar' => 'الفرنسية'],
        'Anglais' => ['fr_FR' => 'Anglais', 'en_US' => 'English', 'ar' => 'الإنجليزية'],
        'Mathématiques' => ['fr_FR' => 'Mathématiques', 'en_US' => 'Mathematics', 'ar' => 'الرياضيات'],
        'Histoire - Géographie' => ['fr_FR' => 'Histoire - Géographie', 'en_US' => 'History - Geography', 'ar' => 'التاريخ والجغرافيا'],
        'Physique - Chimie' => ['fr_FR' => 'Physique - Chimie', 'en_US' => 'Physics - Chemistry', 'ar' => 'الفيزياء والكيمياء'],
        'SVT' => ['fr_FR' => 'SVT (Sciences de la Vie et de la Terre)', 'en_US' => 'Biology & Earth Sciences', 'ar' => 'علوم الحياة والأرض'],
        'Philosophie' => ['fr_FR' => 'Philosophie', 'en_US' => 'Philosophy', 'ar' => 'الفلسفة'],
        'EPS' => ['fr_FR' => 'Éducation Physique et Sportive', 'en_US' => 'Physical Education', 'ar' => 'التربية البدنية والرياضية'],
        'Informatique' => ['fr_FR' => 'Informatique', 'en_US' => 'Computer Science', 'ar' => 'الحاسوب'],
        'Arabe' => ['fr_FR' => 'Arabe', 'en_US' => 'Arabic', 'ar' => 'العربية'],
        'Devoir' => ['fr_FR' => 'Devoir', 'en_US' => 'Assignment', 'ar' => 'واجب'],
        'Composition' => ['fr_FR' => 'Composition', 'en_US' => 'Exam', 'ar' => 'اختبار'],
        'Interrogation' => ['fr_FR' => 'Interrogation', 'en_US' => 'Quiz', 'ar' => 'استجواب'],
        "Le Chef d'établissement" => [
            'fr_FR' => "Le Chef d'établissement",
            'en_US' => "School Headmaster",
            'ar'    => "مدير المؤسسة"
        ],
        'Très Bien' => [
            'fr_FR' => 'Très Bien',
            'en_US' => 'Very Good',
            'ar'    => 'ممتاز'
        ],
        'Bien' => [
            'fr_FR' => 'Bien',
            'en_US' => 'Good',
            'ar'    => 'جيد'
        ],
        'Assez Bien' => [
            'fr_FR' => 'Assez Bien',
            'en_US' => 'Fairly Good',
            'ar'    => 'جيد جداً'
        ],
        'Passable' => [
            'fr_FR' => 'Passable',
            'en_US' => 'Satisfactory',
            'ar'    => 'مقبول'
        ],
        'Insuffisant' => [
            'fr_FR' => 'Insuffisant',
            'en_US' => 'Insufficient',
            'ar'    => 'ضعيف'
        ],
        'Médiocre' => [
            'fr_FR' => 'Médiocre',
            'en_US' => 'Poor',
            'ar'    => 'سيئ'
        ],
        'Provisoire' => [
            'fr_FR' => 'Provisoire',
            'en_US' => 'Provisional',
            'ar'    => 'مؤقت'
        ],
        'Validé' => [
            'fr_FR' => 'Validé',
            'en_US' => 'Validated',
            'ar'    => 'مؤكد'
        ],
        'Publié' => [
            'fr_FR' => 'Publié',
            'en_US' => 'Published',
            'ar'    => 'منشور'
        ]
    ];

    /**
     * Resolves canonical locale code from raw language configuration string.
     */
    public static function normalizeLang(?string $langInput, string $default = 'fr_FR'): string {
        if (empty($langInput)) return $default;
        $map = [
            'fr' => 'fr_FR', 'fr_FR' => 'fr_FR', 'Francais' => 'fr_FR', 'Français' => 'fr_FR',
            'en' => 'en_US', 'en_US' => 'en_US', 'Anglais' => 'en_US', 'English' => 'en_US',
            'ar' => 'ar', 'Arabe' => 'ar', 'Arabic' => 'ar'
        ];
        return $map[$langInput] ?? $map[explode('_', $langInput)[0]] ?? $default;
    }

    /**
     * Checks whether a given language is RTL (e.g., Arabic).
     */
    public static function isRtl(string $langCode): bool {
        return self::normalizeLang($langCode) === 'ar';
    }

    /**
     * Translates a given institutional msgid into a target language code.
     * Uses dictionary fallback first, then gettext.
     */
    public static function translateTo(string $msgid, string $langCode): string {
        $normLang = self::normalizeLang($langCode);
        if (isset(self::$dictionary[$msgid][$normLang])) {
            return self::$dictionary[$msgid][$normLang];
        }

        // Native gettext fallback
        $oldLang = $_SESSION['lang'] ?? 'fr_FR';
        putenv("LC_ALL={$normLang}");
        putenv("LANG={$normLang}");
        putenv("LANGUAGE={$normLang}");
        $translated = gettext($msgid);
        putenv("LC_ALL={$oldLang}");
        putenv("LANG={$oldLang}");
        putenv("LANGUAGE={$oldLang}");
        return $translated ?: $msgid;
    }

    /**
     * Renders a label according to the lycee's param_general settings.
     * If nb_langue == 1: returns L1 translated string.
     * If nb_langue == 2: returns "L1 / L2" (with RTL wrapper if L2 is RTL).
     *
     * @param string $msgid Key institutional label in French.
     * @param array $paramGeneral Result of ParamGeneral::findByLyceeId()
     * @return string HTML/Text formatted label string.
     */
    public static function label(string $msgid, array $paramGeneral): string {
        $nbLangue = (int)($paramGeneral['nb_langue'] ?? 1);
        $multilingueActif = isset($paramGeneral['multilingue_actif']) ? (int)$paramGeneral['multilingue_actif'] : 1;
        $lang1 = self::normalizeLang($paramGeneral['langue_1'] ?? 'fr_FR');
        $lang2 = self::normalizeLang($paramGeneral['langue_2'] ?? 'en_US');

        $t1 = self::translateTo($msgid, $lang1);

        if ($nbLangue < 2 || empty($paramGeneral['langue_2']) || $lang1 === $lang2) {
            if (self::isRtl($lang1)) {
                return '<span dir="rtl" class="rtl-text">' . htmlspecialchars($t1) . '</span>';
            }
            return htmlspecialchars($t1);
        }

        $t2 = self::translateTo($msgid, $lang2);

        if (self::isRtl($lang2)) {
            return htmlspecialchars($t1) . ' / <span dir="rtl" class="rtl-text">' . htmlspecialchars($t2) . '</span>';
        } elseif (self::isRtl($lang1)) {
            return '<span dir="rtl" class="rtl-text">' . htmlspecialchars($t1) . '</span> / ' . htmlspecialchars($t2);
        }

        return htmlspecialchars($t1) . ' / ' . htmlspecialchars($t2);
    }
}
