<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEmployees;
use Exception;
use Log;
use Illuminate\Http\Request;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use App\Models\JobBatch;

class UploadController extends Controller
{
    // For main page view.

    public function index()
    {
        return view('upload');
    }

    // For file upload process Progress.

    public function progress()
    {
        return view('progress');
    }

    /**
     * Upload File And Store In Database.
     *
     * @param Request $request
     * @return void
     */
    public function uploadFileAndStoreInDatabase(Request $request)
    {
        try
        {
            if($request->has('csvFile'))
            {
                $fileName = $request->csvFile->getClientOriginalName();
                $fileWithPath = public_path('uploads').'/'.$fileName;

                if(!file_exists($fileWithPath))
                {
                    $request->csvFile->move(public_path('uploads'), $fileName);
                }

                $header = null;
                $dataFromcsv = array();
                $records = array_map('str_getcsv', file($fileWithPath));

                // Re arranging the data.

                foreach($records as $record)
                {
                    if(!$header)
                    {
                        $header = $record;
                    }
                    else
                    {
                        $dataFromcsv[] = $record;
                    }
                }

                // breaking data for examle 10k to 1k/300 each.

                $dataFromcsv = array_chunk($dataFromcsv, 300);
                $batch = Bus::batch([])->dispatch();
                // Looping through each 1000/300 employees.
                foreach($dataFromcsv as $index => $dataCsv)
                {
                    // looping through each employee data.
                    foreach($dataCsv as $data)
                    {
                        $employeeData[$index][] = array_combine($header, $data);
                    }
                    $batch->add(new ProcessEmployees($employeeData[$index]));
                    //ProcessEmployees::dispatch($employeeData[$index]);
                }

                // We update session id every time we process new batch.

                session()->put('lastBatchId', $batch->id);

                return redirect('/progress?id='. $batch->id);
            }
        }
        catch(Exception $e)
        {
            Log::error($e);
            dd($e);
        }
    }


    /**
     * Function gets the progress while obs execute.
     */
    public function progressForCsvStoreProcess(Request $request)
    {
        try
        {
            $batchId = $request->id ?? session()->get('lastBatchId');
            
            if(JobBatch::where('id', $batchId)->count())
            {
                $response = JobBatch::where('id', $batchId)->first();
                return response()->json($response);
            }
        }
        catch(Exception $e)
        {
            Log::error($e);
            dd($e);
        }
    }
}
