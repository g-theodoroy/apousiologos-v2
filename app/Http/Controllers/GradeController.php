<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Grade;
use App\Models\Tmima;
use App\Models\Period;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Anathesi;
use App\Exports\BathmologiaExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class GradeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    /*
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('allow.grades');
    }
*/

    public function index($selectedAnathesiId = 0)
    {
        $activeGradePeriod = Setting::getValueOf('activeGradePeriod');
        $gradeBaseAlert = intVal(Setting::getValueOf('gradeBaseAlert'));

        $selectedTmima = null;
        $selectedMathima = null;
        $gradesStudentsPeriod = null;
        $periods = config('gth.periods');
        unset($periods[key($periods)]);

        if ($selectedAnathesiId) {
            $selectedAnathesi = Anathesi::find($selectedAnathesiId);
            $selectedTmima = $selectedAnathesi->tmima;
            $selectedMathima = $selectedAnathesi->mathima;

            // βάζω σε ένα πίνακα τους ΑΜ των μαθητών που ανήκουν στο επιλεγμένο τμήμα
            $student_ids = Tmima::where('tmima', $selectedTmima)->pluck('student_id')->toArray();


            $grades = Grade::where('anathesi_id', $selectedAnathesiId)->where('period_id', $activeGradePeriod)->pluck('grade', 'student_id');
            $allGrades = Grade::where('anathesi_id', $selectedAnathesiId)->get(['grade', 'student_id', 'period_id'])->toArray();
            $gradesStudentsPeriod = array();

            // αρχικοποιώ τις τιμές σε null
            foreach ($student_ids as $student_id) {
                foreach ($periods as $periodKey => $period) {
                    $gradesStudentsPeriod[$student_id][$periodKey] = null;
                }
            }
            // εισάγω τις υπάρχουσες
            foreach ($allGrades as $gr) {
                $gradesStudentsPeriod[$gr['student_id']][$gr['period_id']] = $gr['grade'];
            }
        }


        // παίρνω τα τμηματα του χρήστη
        // ταξινόμηση με το μήκος του ονόματος + αλφαβητικά
        $anatheseis = Auth::user()->anatheseis()->orderby('mathima')->orderByRaw('LENGTH(tmima)')->orderby('tmima')->get(['id', 'mathima', 'tmima']);

        // αν είναι Διαχειριστής τα παίρνω όλα από μια φορά
        if (Auth::user()->role_id == 1) {
            $anatheseis = Anathesi::orderby('mathima')->orderByRaw('LENGTH(tmima)')->orderby('tmima')->get(['id', 'mathima', 'tmima']);
        }

        $students = array();

        if ($selectedTmima) {
            // παίρνω τα στοιχεία των μαθητών ταξινομημένα κσι φιλτράρω μόνο τους ΑΜ που έχει το τμήμα
            $students = Student::orderby('eponimo')->orderby('onoma')->orderby('patronimo')->with('tmimata')->with('anatheseis')->get()->only($student_ids);
        }

        $arrStudents = array();
        $gradesPeriodLessons = array();
        foreach ($students as $stuApFoD) {
            foreach ($stuApFoD->anatheseis as $anath) {
                $gradesPeriodLessons[$stuApFoD->id]['name'] = $stuApFoD->eponimo . ' ' . $stuApFoD->onoma;
                $gradesPeriodLessons[$stuApFoD->id][$anath->mathima][$anath->pivot->period_id] = $anath->pivot->grade;
            }
            $tmimata = $stuApFoD->tmimata->pluck('tmima');
            $arrStudents[] = [
                'id' => $stuApFoD->id,
                'eponimo' => $stuApFoD->eponimo,
                'onoma' => $stuApFoD->onoma,
                'patronimo' => $stuApFoD->patronimo,
                'tmima' => $tmimata[0],
                'tmimata' => $tmimata->implode(', '),
                'grade' => $grades[$stuApFoD->id] ?? null,
                'olografos' => isset($grades[$stuApFoD->id])  ? $this->olografos($grades[$stuApFoD->id]) : null
            ];
        }


        usort($arrStudents, function ($a, $b) {
            return $a['eponimo'] <=> $b['eponimo'] ?:
                $a['onoma'] <=> $b['onoma'] ?:
                strnatcasecmp($a['patronimo'], $b['patronimo']);
        });

        $mathimata = Anathesi::select('mathima')->distinct()->orderBy('mathima')->pluck('mathima')->toArray();

        $showOtherGrades = Setting::getValueOf('showOtherGrades') == 1 ?? false;

        return Inertia::render('Grades', [
            'anatheseis' => $anatheseis,
            'selectedAnathesiId' => intval($selectedAnathesiId),
            'selectedTmima' => $selectedTmima,
            'selectedMathima' => $selectedMathima,
            'arrStudents' => $arrStudents,
            'gradesStudentsPeriod' => $gradesStudentsPeriod,
            'gradesPeriodLessons' => $gradesPeriodLessons,
            'mathimata' => $mathimata,
            'activeGradePeriod' => $activeGradePeriod,
            'periods' => $periods,
            'showOtherGrades' => $showOtherGrades,
            'gradeBaseAlert' => $gradeBaseAlert
        ]);
    }


    public function store($selectedAnathesiId = 0)
    {

        $activeGradePeriod = Setting::getValueOf('activeGradePeriod');
        $data = request()->all();

        foreach ($data as $am => $periods) {
            $grade = $periods[$activeGradePeriod];
            if ($grade || $grade == 0) {
                Grade::updateOrCreate(['anathesi_id' => $selectedAnathesiId, 'student_id' =>  $am, 'period_id' =>  $activeGradePeriod], [
                    'grade' => str_replace(".", ",", $grade),
                ]);
            } else {
                Grade::where('anathesi_id', $selectedAnathesiId)->where('student_id', $am)->where('period_id', $activeGradePeriod)->delete();
            }
        }

        return redirect("grades/$selectedAnathesiId")->with(['message' => 'Επιτυχής ενημέρωση.']);
    }

    public function exportGradesXls()
    {
        $filename = Setting::getValueOf('schoolName') . ' - ' . config('gth.periods')[Setting::getValueOf('activeGradePeriod')] . ' - 187.xls';
        return Excel::download(new BathmologiaExport, $filename);
    }

    public function olografos($n)
    {
        if ($n == 'Δ') return "Όχι άποψη";
        if (in_array($n,['0', '00', '00,0']) ) return "μηδέν";
        if ($n == '-1') return "Δεν προσήλθε";
        $num = floatval(str_replace(',', '.', $n));
        if ($num > 20) return null;
        if (!$num || $num < 0) return null;
        $whole = intval($num);
        $fraction = substr($num - $whole, -1);
        $numberNames = ['μηδέν', 'ένα', 'δύο', 'τρία', 'τέσσερα', 'πέντε', 'έξι', 'επτά', 'οκτώ', 'εννέα', 'δέκα', 'έντεκα', 'δώδεκα', 'δεκατρία', 'δεκατέσσερα', 'δεκαπέντε', 'δεκαέξι', 'δεκαεπτά', 'δεκαοκτώ', 'δεκαεννέα', 'είκοσι'];
        $name = $numberNames[$whole];
        if ($fraction > 0) $name .= ' κ ' . $numberNames[$fraction];
        return  $name;
    }
}
