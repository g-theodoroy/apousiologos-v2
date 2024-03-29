<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Apousie;
use App\Models\Program;
use App\Models\Setting;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ApousiesForDayExport;
use App\Services\ApousiologosService;

class ApousiologosController extends Controller
{

  public function index(ApousiologosService $apousiologosService,  $selectedTmima = '0', $postDate = null)
  {

    return Inertia::render('Apousiologos', $apousiologosService->indexCreateData($selectedTmima, $postDate));

  }

  public function store($selectedTmima = '0', $date = null)
  {

    // παίρνω τα στοιχεία των απουσιών (τιμες boolean true - false)
    $data = request()->except(['date']);
    $postDate = request('date');
    $date = str_replace("-", "", $postDate);
    $numOfHours = Program::getNumOfHours();
    $allowTeachersEditOthersApousies = Setting::getValueOf('allowTeachersEditOthersApousies');

    // αρχικοποιώ την ημέρα αν δεν έχει έρθει με το url
    if (!$date) $date = Carbon::now()->format("Ymd");

    // αρχικοποίηση string απουσιών "0000000"
    $initApouValue = str_repeat("0", $numOfHours);
    $initApovValue = str_repeat("0", $numOfHours);

    // φτιάχνω την τιμή για αποθήκευση '1100100'
    foreach ($data as $key => $arrValue) {

      $valueApou = '';
      foreach ($arrValue['apou'] as $val) {
        $val == true ? $valueApou .= '1' :  $valueApou .= '0';
      }

      $valueApov = '';
      foreach ($arrValue['apov'] as $val) {
        $val == true ? $valueApov .= '1' :  $valueApov .= '0';
      }

      $valueTeach = '';
      foreach ($arrValue['teach'] as $val) {
        //dd($val);
        $val  ?   $valueTeach .= $val . '-' : $valueTeach .= '0-';
      }
      $valueTeach = rtrim($valueTeach,'-');

      // αν δεν υπάρχουν απουσίες '0000000' δεν θα εισάγω τιμές
      if ($valueApou == $initApouValue) $valueApou = '';

      // αν δεν είναι κενό ενημερώνω αν υπάρχει ΑΜ+ημνια ή πρόσθέτω
      if ($valueApou) {
        $apousia = Apousie::where('student_id', $key)->where('date', $date)->first();
        if (!$apousia) {
          Apousie::create([
            'student_id' => $key,
            'date' => $date,
            'apousies' => $valueApou,
            'apovoles' => $valueApov == $initApovValue ? '' : $valueApov,
            'teachers' => $valueTeach
          ]);
        } else {
          $apousia->apousies = $valueApou;
          $apousia->apovoles = $valueApov == $initApovValue ? '' : $valueApov;
          $apousia->teachers = $valueTeach;
          $apousia->save();
        }
      } else {
        // αν είναι κενό διαγράφω αν υπάρχει ΑΜ+ημνια
        Apousie::where('student_id', $key)->where('date', $date)->delete();
      }
    }

    $dateShow = Carbon::createFromFormat("Y-m-d", $postDate)->format("d/m/Y");

    return redirect("/apousiologos/$selectedTmima/$postDate")->with(['message' => ['saveSuccess' => "Kαταχώριση απουσιών για τις $dateShow επιτυχής."]]);

  }

  /**
   * Την καλώ από τον Apousiologos.vue το κουμπί εξαγωγή δίπλα από την επιλογή Ημερομηνίας και
   * από Διαχείριση -> Εξαγωγή xls -> Εξαγωγή απουσιών για τις ημερομηνίες -> Εξαγωγή xls
   */
  public function exportApousiesXls()
  {
    $apoDate = str_replace("-", "", request()->apoDate);
    $eosDate = str_replace("-", "", request()->eosDate);
    if ($apoDate && $eosDate) {
      if ($apoDate == $eosDate) {
        $filenameDates = '_για_τις_' . $apoDate;
      } else {
        $filenameDates = '_από_' . $apoDate . '_έως_' . $eosDate;
      }
    } elseif (!$apoDate && $eosDate) {
      $filenameDates = '_έως_τις_' . $eosDate;
    } elseif ($apoDate && !$eosDate) {
      $filenameDates = '_από_τις_' . $apoDate;
    } else {
      $filenameDates = '_για_τις_' . Carbon::now()->format("Ymd");
    }

    return Excel::download(new ApousiesForDayExport($apoDate, $eosDate), 'myschool_Eisagwgh_Apousiwn_Mazika_apo_Excel_by_GΘ' . $filenameDates . '.xls');
  }

}
