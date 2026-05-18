<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Specialite;
use App\Models\Project;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Spécialités
        $tc = Specialite::create(['nom' => 'Tronc commun', 'code' => 'TC', 'description' => 'Première année tronc commun']);
        $info = Specialite::create(['nom' => 'Informatique', 'code' => 'INFO', 'description' => 'Spécialité informatique']);
        $elec = Specialite::create(['nom' => 'Électronique / Télécom', 'code' => 'EIT', 'description' => 'Spécialité électronique et télécommunications']);

        // 2. RUP Projet
        User::create([
            'name' => 'AKAFOU',
            'prenom' => 'Responsable',
            'email' => 'rup.projet@inp.edu.ci',
            'password' => Hash::make('password'),
            'role' => 'rup_projet',
            'annee_universitaire' => '2024/2025',
        ]);

        // 3. Professeurs et RUP Spécialité
        $professeurs = [
            // RUP Spécialité TC
            ['KPO LOUA', 'WAGO RICHARD', 'kpo.loua@inp.edu.ci', 'rup_specialite', 'TC', true],
            // RUP Spécialité EIT
            ['MOUSSOH EDIGBEGBE', 'RAYMOND', 'moussoh.raymond@inp.edu.ci', 'rup_specialite', 'EIT', true],
            // RUP Spécialité INFO
            ['ASSALÉ', 'ADJE LOUIS', 'assale.louis@inp.edu.ci', 'rup_specialite', 'INFO', true],
            // Professeurs INFO
            ['KIMOU KOUADIO', 'PROSPER', 'kimou.prosper@inp.edu.ci', 'professeur', 'INFO', false],
            ['ALLA AHUI', 'BENJAMIN', 'alla.benjamin@inp.edu.ci', 'professeur', 'INFO', false],
            ['KEUPONDJO', 'ARMEL', 'keupondjo.armel@inp.edu.ci', 'professeur', 'INFO', false],
            ['BLA BATHELEMY', '', 'bla.bathelemy@inp.edu.ci', 'professeur', 'INFO', false],
            ['ANGHU BEATRICE', '', 'anghu.beatrice@inp.edu.ci', 'professeur', 'INFO', false],
            ['APATA', '', 'apata@inp.edu.ci', 'professeur', 'INFO', false],
            ['APPOH', '', 'appoh@inp.edu.ci', 'professeur', 'INFO', false],
            ['BOUSSOU HYPOLITE', '', 'boussou.hypolite@inp.edu.ci', 'professeur', 'INFO', false],
            ['GBAMELE', '', 'gbamele@inp.edu.ci', 'professeur', 'INFO', false],
            ['KIMOU', '', 'kimou@inp.edu.ci', 'professeur', 'INFO', false],
            ['KOFFI YAO ISMAËL', '', 'koffi.ismael@inp.edu.ci', 'professeur', 'INFO', false],
            ['KONAN KOFFI', '', 'konan.koffi@inp.edu.ci', 'professeur', 'INFO', false],
            ['KONAN N\'DRI', '', 'konan.ndri@inp.edu.ci', 'professeur', 'INFO', false],
            ['KONANKAN', '', 'konankan@inp.edu.ci', 'professeur', 'INFO', false],
            ['KONÉ BRAHIMAN', '', 'kone.brahiman@inp.edu.ci', 'professeur', 'INFO', false],
            ['KONE SIRIKY', '', 'kone.siriky@inp.edu.ci', 'professeur', 'INFO', false],
            ['KONE S.', '', 'kone.s@inp.edu.ci', 'professeur', 'INFO', false],
            ['KOSSONOU', '', 'kossonou@inp.edu.ci', 'professeur', 'INFO', false],
            ['KOUADIO AMANI', '', 'kouadio.amani@inp.edu.ci', 'professeur', 'INFO', false],
            ['KOUAKOU GEOFFROY', '', 'kouakou.geoffroy@inp.edu.ci', 'professeur', 'INFO', false],
            ['KOUAME KOUAKOU', '', 'kouame.kouakou@inp.edu.ci', 'professeur', 'INFO', false],
            ['ONIANO NEE MOBIO', '', 'oniano.mobio@inp.edu.ci', 'professeur', 'INFO', false],
            ['SYLLA MARIAM', '', 'sylla.mariam@inp.edu.ci', 'professeur', 'INFO', false],
            ['N\'GUESSAN KROPKA', '', 'nguessan.kropka@inp.edu.ci', 'professeur', 'INFO', false],
            ['YAO KOUAKOU GUILLAUME', '', 'yao.guillaume@inp.edu.ci', 'professeur', 'INFO', false],
            ['YEO ZIÉ', '', 'yeo.zie@inp.edu.ci', 'professeur', 'INFO', false],
            // Professeurs EIT
            ['DJ AHA KOFFI', '', 'djaha.koffi@inp.edu.ci', 'professeur', 'EIT', false],
            ['DEGNI', '', 'degni@inp.edu.ci', 'professeur', 'EIT', false],
            ['DEROH', '', 'deroh@inp.edu.ci', 'professeur', 'EIT', false],
        ];

        foreach ($professeurs as $prof) {
            User::create([
                'name' => $prof[0],
                'prenom' => $prof[1] ?: '',
                'email' => $prof[2],
                'password' => Hash::make('password'),
                'role' => $prof[3],
                'specialite_id' => Specialite::where('code', $prof[4])->first()->id,
                'annee_universitaire' => '2024/2025',
            ]);
        }

        // ✅ SECTION SUPPRIMÉE : Plus aucun projet archivé créé ici

        // 4. Étudiants
        $etudiants = [
            // TS1 A (tous TC)
            ['25INP00614', 'AMICHIA', 'ALLOU JEAN-PRINCE', 'M', 'TC', 'TS1'],
            ['25INP00502', 'BLÉ', 'BATIYE ESPOIR KOFFI', 'M', 'TC', 'TS1'],
            ['25INP00712', 'DIARRASSOUBA', 'SOUMAÏLA', 'M', 'TC', 'TS1'],
            ['25INP01279', 'DOUMBIA', 'AROUNA', 'M', 'TC', 'TS1'],
            ['25INP00778', 'FOFANA', 'AMARA DAOUD', 'M', 'TC', 'TS1'],
            ['25INP01319', 'GBONGUE', 'DROH SEM ELIPHAZ', 'M', 'TC', 'TS1'],
            ['25INP00460', 'KEBE', 'ORLI GIFT TRESOR', 'M', 'TC', 'TS1'],
            ['25INP00522', 'KEITA', 'ABDOUL', 'M', 'TC', 'TS1'],
            ['25INP01294', 'KIE BI ZAH', 'CHRIST-YVANN DAVID', 'M', 'TC', 'TS1'],
            ['25INP01245', 'KOFFI', 'AMENAN MARIE CHRISTELLE', 'F', 'TC', 'TS1'],
            ['25INP00455', 'KONE', 'ABOU', 'M', 'TC', 'TS1'],
            ['25INP00985', 'KOUADIO', 'KOUASSI JEAN HONORAT', 'M', 'TC', 'TS1'],
            ['25INP00499', 'KOUADIO YAO N\'DA', 'KONAN BORIS', 'M', 'TC', 'TS1'],
            ['25INP00782', 'KOUAMÉ', 'AFFOUA EUNICE ANDRÉA', 'F', 'TC', 'TS1'],
            ['25INP00794', 'KOUAME', 'MIENMOH ESLI MICHAEL', 'M', 'TC', 'TS1'],
            ['25INP01283', 'KOUASSI', 'ASSEMIEN LUDOVIC', 'M', 'TC', 'TS1'],
            ['25INP01426', 'KOUASSI', 'KOFFI CHANCE EMMANUEL', 'M', 'TC', 'TS1'],
            ['25INP00270', 'KOUASSI N\'GUESSAN', 'PAUL MARIE DONATIEN', 'M', 'TC', 'TS1'],
            ['25INP01437', 'OCHO', 'AMA JUNIOR', 'M', 'TC', 'TS1'],
            ['25INP01422', 'OGUEHI', 'SOPHONIE ULRICH GODFROY', 'M', 'TC', 'TS1'],
            ['25INP01144', 'RAHEEM', 'HADIYATOU OMOLARA AMOPE', 'F', 'TC', 'TS1'],
            ['25INP00958', 'SERY', 'GRACE ESLIE MELAINE', 'F', 'TC', 'TS1'],
            ['25INP00441', 'SILUE', 'KSONGUI YASSIN AMAR', 'M', 'TC', 'TS1'],
            ['25INP00787', 'SORO', 'NATANHANNAN ABOUBAKAR SIRIKI', 'M', 'TC', 'TS1'],
            ['25INP01246', 'SORO', 'YARTCHOUMA ISMAEL', 'M', 'TC', 'TS1'],
            ['25INP00434', 'SYLLA', 'MAMADOU', 'M', 'TC', 'TS1'],
            ['25INP01153', 'TOHOURA LOU', 'MAYA ASHLEY SARAH', 'F', 'TC', 'TS1'],
            ['25INP00966', 'TRAORE', 'KARIDJATOU', 'F', 'TC', 'TS1'],
            ['25INP01257', 'YAO', 'KOUAME JOËL', 'M', 'TC', 'TS1'],
            ['25INP01326', 'ZAKIFADA', 'KIARI GOGE AMADOU', 'M', 'TC', 'TS1'],
            // TS1 B
            ['24INP00821', 'ADOU', 'CHRIST-CORLIS CENDERSON', 'M', 'TC', 'TS1'],
            ['25INP00710', 'AKA', 'EFFOLY JEFFDENON', 'M', 'TC', 'TS1'],
            ['25INP01430', 'BEMBAMBA', 'ABOUBACAR SIDIK', 'M', 'TC', 'TS1'],
            ['25INP01432', 'BODJI BAFFOLE', 'OKPOBE MARIE YVETTE', 'F', 'TC', 'TS1'],
            ['25INP00686', 'COULIBALY', 'ZIÉ IBRAHIM MALICK', 'M', 'TC', 'TS1'],
            ['25INP01155', 'DOUHO', 'GORHI WILFRIED', 'M', 'TC', 'TS1'],
            ['25INP00825', 'GNAHORE', 'ANGE ERIC', 'M', 'TC', 'TS1'],
            ['24INP00582', 'KANLE BI', 'BOTI ENOCK', 'M', 'TC', 'TS1'],
            ['25INP00845', 'KARAMOKO', 'HASSAN CHEICK IMAD', 'M', 'TC', 'TS1'],
            ['25INP00844', 'KASSY KABLAN', 'PIERRE YVANN', 'M', 'TC', 'TS1'],
            ['25INP01440', 'KAUPHY', 'CARLYLLE MARIE-EDEN', 'F', 'TC', 'TS1'],
            ['25INP00760', 'KOFFI', 'ANSAH MAX DANIEL', 'M', 'TC', 'TS1'],
            ['25INP00891', 'KOFFI KOUASSI', 'CHRISTIAN BEKAN N\'TI', 'M', 'TC', 'TS1'],
            ['25INP00279', 'KONAN', 'YAO BLANCHARD', 'M', 'TC', 'TS1'],
            ['25INP01099', 'KONÉ NANGBAMA', 'AFFOUÉ DÉSIRÉE JOSETTE', 'F', 'TC', 'TS1'],
            ['25INP00303', 'KONE', 'NAVIGUE', 'M', 'TC', 'TS1'],
            ['25INP00763', 'KOUAME', 'BERAH AUDREY ARIANE', 'F', 'TC', 'TS1'],
            ['24INP00902', 'KOUAME', 'JOSUE MICAEL', 'M', 'TC', 'TS1'],
            ['25INP00595', 'KOULA', 'GNONSIÉKAN MARIE-EMMANUEL', 'M', 'TC', 'TS1'],
            ['23INP01418', 'KROMAH', 'FLORENCE', 'F', 'TC', 'TS1'],
            ['25INP00729', 'N\'GOUAN AKOA', 'CHRIS-EMMANUEL', 'M', 'TC', 'TS1'],
            ['25INP01428', 'OBESSOU', 'KAUDJHIS ANGE MERLIN', 'M', 'TC', 'TS1'],
            ['25INP01259', 'OUATTARA', 'ADAMA', 'M', 'TC', 'TS1'],
            ['25INP00330', 'OUATTARA', 'KOLO PHARES', 'M', 'TC', 'TS1'],
            ['25INP00383', 'SAMAKÉ', 'ESTHER ANGE SALIMATA', 'F', 'TC', 'TS1'],
            ['25INP00420', 'SEHI', 'KEASSAI CHRIST', 'M', 'TC', 'TS1'],
            ['24INP00156', 'TAKY', 'KOFFI JEAN MARTIAL', 'M', 'TC', 'TS1'],
            ['24INP01405', 'VARNEY', 'JENNEBAH ZANNABU', 'F', 'TC', 'TS1'],
            ['25INP00702', 'YAO', 'KOUAME CHRIST ALEX', 'M', 'TC', 'TS1'],
            // TS2 Informatique
            ['24INP00564', 'ADJE', 'CHRYS-RENÉ YORAM', 'M', 'INFO', 'TS2'],
            ['24INP00224', 'ADON', 'HONORAT KIM ASTRID MARILYNN MAEVA', 'F', 'INFO', 'TS2'],
            ['24INP01179', 'BAH', 'AMADOU OURY', 'M', 'INFO', 'TS2'],
            ['24INP00318', 'BAMBA', 'IBRAHIM KADER', 'M', 'INFO', 'TS2'],
            ['24INP01190', 'DAKOI', 'KOBENAN ISAAC', 'M', 'INFO', 'TS2'],
            ['24INP00820', 'EKPONZA', 'CHRIST EDEN', 'M', 'INFO', 'TS2'],
            ['24INP00899', 'ETTIEGNE', 'MARIE KAROL GIOVANNI KASSI', 'M', 'INFO', 'TS2'],
            ['23INP01404', 'FLOMO', 'JOSIAH GEORGE', 'M', 'INFO', 'TS2'],
            ['24INP00764', 'FOFANA', 'ABIBA', 'F', 'INFO', 'TS2'],
            ['24INP00771', 'GNANAGO', 'GUEMO BRUNO', 'M', 'INFO', 'TS2'],
            ['24INP00148', 'GRAMBOUTE', 'MOHAMED PRINCE', 'M', 'INFO', 'TS2'],
            ['24INP00563', 'KABRAN', 'CHRIS LOUIS-MICHEL', 'M', 'INFO', 'TS2'],
            ['24INP00769', 'KARIMOU', 'HICHAM', 'M', 'INFO', 'TS2'],
            ['24INP00722', 'KOUAO', 'DARRYL PATRICK JUNIOR', 'M', 'INFO', 'TS2'],
            ['24INP00910', 'KOUASSI NDA', 'HANAN CHRIST MARVINE', 'M', 'INFO', 'TS2'],
            ['24INP00231', 'KOUASSI', 'VALDES MOAYE', 'M', 'INFO', 'TS2'],
            ['24INP00866', 'MAÏZAN BONI', 'KOBENAN JEAN DOMINIQUE DIEUDONNÉ', 'M', 'INFO', 'TS2'],
            ['24INP00507', 'N\'GUESSAN', 'N\'GORAN DANIEL', 'M', 'INFO', 'TS2'],
            ['24INP00624', 'OJOAWO', 'OLUFEMI KOUA TIMOTHÉE', 'M', 'INFO', 'TS2'],
            ['23INP01402', 'PETERS', 'GRACE NUMGBO BONO', 'F', 'INFO', 'TS2'],
            ['22INP01271', 'SANDO', 'JAMESETTA JARWILLIE', 'F', 'INFO', 'TS2'],
            ['24INP00438', 'SORO', 'GNENINGNIME MICHEL', 'M', 'INFO', 'TS2'],
            ['24INP00454', 'SORO', 'NAVIGUEH BEN IDRISS', 'M', 'INFO', 'TS2'],
            ['24INP01160', 'SOUNDELE', 'KONAN HENIH MOH', 'F', 'INFO', 'TS2'],
            ['24INP00334', 'TRAORE', 'M\'BETCHIE WALAWOGNIGUI ANGE-EMMANUEL', 'M', 'INFO', 'TS2'],
            ['24INP00774', 'TRAZIE BI', 'JONATHAN', 'M', 'INFO', 'TS2'],
            ['24INP00754', 'ZOH', 'GONSEU JEAN MICHEL', 'M', 'INFO', 'TS2'],
            ['24INP00195', 'ZORO BI', 'TRAZIE JULIEN HABIB', 'M', 'INFO', 'TS2'],
            // TS2 EIT
            ['24INP00956', 'CISSE', 'YAYA', 'M', 'EIT', 'TS2'],
            ['24INP00576', 'COULIBALY', 'ZIE IBRAHIMA', 'M', 'EIT', 'TS2'],
            ['24INP00736', 'DIANE', 'MADJARA LARISSA', 'F', 'EIT', 'TS2'],
            ['24INP00950', 'DOUMBIA', 'ISSIAKA ABDOUL AZIZ', 'M', 'EIT', 'TS2'],
            ['24INP00243', 'FOFANA', 'MAMADOU JUNIOR', 'M', 'EIT', 'TS2'],
            ['24INP00893', 'KABORE', 'ISSA', 'M', 'EIT', 'TS2'],
            ['24INP00804', 'KANGAH', 'KOUAKOU HENRI JOEL', 'M', 'EIT', 'TS2'],
            ['24INP01152', 'KEUMAHON', 'MAHONTO JEAN JACQUES', 'M', 'EIT', 'TS2'],
            ['24INP00785', 'KOFFI', 'N\'GUESSAN RAOUL', 'M', 'EIT', 'TS2'],
            ['24INP01183', 'KONE', 'HAMED', 'M', 'EIT', 'TS2'],
            ['24INP00719', 'KOUASSI FAMIEN', 'WATIKE SOURALAIS ATINN', 'M', 'EIT', 'TS2'],
            ['24INP00759', 'N\'GBESSO', 'CHRIST URIEL JUNIOR', 'M', 'EIT', 'TS2'],
            ['24INP00825', 'OCHOU OCHOU', 'JUNIOR ERVIN EMMANUEL', 'M', 'EIT', 'TS2'],
            ['24INP00268', 'OUATTARA', 'ABDALLAH', 'M', 'EIT', 'TS2'],
            ['24INP00296', 'OUATTARA', 'CHEICK ABOUBACAR JUNIOR', 'M', 'EIT', 'TS2'],
            ['24INP00649', 'SAGNON', 'NALOUROUGO BRAHIMA JUSTIN', 'M', 'EIT', 'TS2'],
            ['24INP00467', 'SERME', 'ISSOUF', 'M', 'EIT', 'TS2'],
            ['24INP00152', 'SORHO', 'DONAPORGÔ DAVID-PAUL', 'M', 'EIT', 'TS2'],
            ['24INP00932', 'TIE', 'ARMEL AIME', 'M', 'EIT', 'TS2'],
            ['24INP00428', 'TOURE', 'SIE GNINDANFOWA ABDOUL KADER', 'M', 'EIT', 'TS2'],
            // TS3 Informatique
            ['23INP00996', 'ADO', 'OSEE SOSTHENE', 'M', 'INFO', 'TS3'],
            ['23INP01001', 'ADOU', 'GRACE DIVINE', 'F', 'INFO', 'TS3'],
            ['23INP00262', 'ANOMAN', 'SIBAH CHRIST-YOHANN', 'M', 'INFO', 'TS3'],
            ['23INP00747', 'BAHOUA', 'ELYSE ANGE STEPHANE', 'M', 'INFO', 'TS3'],
            ['22INP01379', 'BORBOR', 'RICHARD TAMBA KALLON', 'M', 'INFO', 'TS3'],
            ['23INP00421', 'COULIBALY', 'SEKOU YEFOUGN-GNIGUI', 'M', 'INFO', 'TS3'],
            ['22INP00735', 'DIABY', 'ADAM', 'M', 'INFO', 'TS3'],
            ['23INP00619', 'EDIAWO', 'KASSIBRA MARIE', 'F', 'INFO', 'TS3'],
            ['23INP00997', 'GNIMADI', 'NANCY KIMBERLY BAÏ', 'F', 'INFO', 'TS3'],
            ['23INP00312', 'ILHYAS', 'RASAQ SALAU', 'M', 'INFO', 'TS3'],
            ['22INP01261', 'JARKAR', 'JOSHUA', 'M', 'INFO', 'TS3'],
            ['23INP00232', 'KONE', 'DOUNTCHE ISSA', 'M', 'INFO', 'TS3'],
            ['22INP00885', 'KOUADIO', 'ABRAHAM', 'M', 'INFO', 'TS3'],
            ['23INP00246', 'KOUAME', 'ISRAEL PIERRE N\'GODIO JUNIOR', 'M', 'INFO', 'TS3'],
            ['23INP01007', 'OUATTARA', 'ZIE BAKARY', 'M', 'INFO', 'TS3'],
            ['23INP00898', 'OYEWUMI', 'ABDUL BASTI ABOLAYO', 'M', 'INFO', 'TS3'],
            ['23INP00503', 'TOURE', 'BABA HASSAN', 'M', 'INFO', 'TS3'],
            ['23INP00893', 'TSIYA', 'KOMI JOSEPH EWOENAN', 'M', 'INFO', 'TS3'],
            // TS3 EIT
            ['23INP00369', 'ANON', 'LORRAINE MARIA FLORINE', 'F', 'EIT', 'TS3'],
            ['23INP00666', 'COULIBALY', 'KOLO MATOSSO', 'F', 'EIT', 'TS3'],
            ['23INP00440', 'DEMBELE', 'MOHAMED ISSOUFOU', 'M', 'EIT', 'TS3'],
            ['23INP00310', 'DIOMANDE', 'VAKARAMOKO', 'M', 'EIT', 'TS3'],
            ['22INP01258', 'DIXON', 'PAXINE', 'F', 'EIT', 'TS3'],
            ['23INP00347', 'KEKEMO', 'BROU PAULE CORALIE', 'F', 'EIT', 'TS3'],
            ['23INP01006', 'KPRIE', 'AMOIN GRACE MARIE AIMEE', 'F', 'EIT', 'TS3'],
            ['23INP00453', 'LANSEU', 'OBED AUREL TRESOR', 'M', 'EIT', 'TS3'],
            ['22INP01265', 'MANLEY', 'GEORGE', 'M', 'EIT', 'TS3'],
            ['23INP00730', 'SANOGO', 'TIEMONGON AMINATA', 'F', 'EIT', 'TS3'],
            ['23INP00561', 'SIDIBÉ', 'ALI', 'M', 'EIT', 'TS3'],
            ['23INP00324', 'SIDIBE', 'MAGNAMA SAMIRA', 'F', 'EIT', 'TS3'],
            ['23INP01002', 'SYLLA', 'ASSITA', 'F', 'EIT', 'TS3'],
            ['23INP00791', 'ZRA BI', 'TIZIE JEREMI', 'M', 'EIT', 'TS3'],
        ];

        foreach ($etudiants as $etud) {
            $email = $etud[0] . '@inp.edu.ci';
            User::create([
                'name' => $etud[1],
                'prenom' => $etud[2],
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'etudiant',
                'specialite_id' => Specialite::where('code', $etud[4])->first()->id,
                'niveau' => $etud[5],
                'annee_universitaire' => '2024/2025',
            ]);
        }
    }
}