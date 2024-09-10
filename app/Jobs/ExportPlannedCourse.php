<?php

namespace App\Jobs;

use App\Models\EduframeUser;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportPlannedCourse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var PlannedCourse The external course code
     */
    protected $plannedCourse;

    /**
     * @var array<int> The user IDs to export
     */
    protected $userIds;

    /**
     * Create a new job instance.
     */
    public function __construct($plannedCourse, $userIds)
    {
        $this->plannedCourse = $plannedCourse;
        $this->userIds = $userIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $rizivNumbers = EduframeUser::whereIn('eduframe_id', $this->userIds)->get();
        $rizivNumbers = $rizivNumbers->pluck('riziv_number')->toArray();

        $rizivNumbers = array_map(function ($rizivNumber) {
            $rizivNumber = htmlspecialchars($rizivNumber);
            $rizivNumber = str_replace(['-', ' '], '', $rizivNumber);
            $rizivNumber = substr($rizivNumber, 1);
            $rizivNumber = substr($rizivNumber, 0, 5);

            return $rizivNumber;
        }, $rizivNumbers);

        Log::debug('Exporting planned course', ['externalCourseCode' => $this->plannedCourse->course->externalCourseCode, 'userIds' => $this->userIds, 'rizivNumbers' => $rizivNumbers]);
        
        $xml = new \SimpleXMLElement('<Entry></Entry>');
        
        $settings = $xml->addChild('Settings');
        $settings->addChild('userID', htmlspecialchars(env('PE_USER_ID')));
        $settings->addChild('userRole', 'EDU');
        $settings->addChild('userKey', htmlspecialchars(env('PE_USER_KEY')));
        $settings->addChild('orgID', htmlspecialchars(env('PE_ORG_ID')));
        $settings->addChild('settingOutput', 1);
        $settings->addChild('emailOutput', htmlspecialchars(env('PE_EMAIL')));
        $settings->addChild('languageID', 1);
        $settings->addChild('defaultLanguageID', 1);

        foreach($rizivNumbers as $rizivNumber) {
            $attendanceElement = $xml->addChild('Attendance');
            $attendanceElement->addChild('externalCourseID', $this->plannedCourse->course->externalCourseCode);
            $attendanceElement->addChild('externalPersonID', $rizivNumber);
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $dom->xmlVersion = '1.0';
        $dom->standalone = true;

        $peClient = new \GuzzleHttp\Client([
            'base_uri' => 'https://acc.pe-online.org/pe-services/pe-attendanceelearning/WriteAttendance.asmx',
            'headers'  => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction'   => 'https://www.pe-online.org/pe-services/PE_AttendanceElearning/WriteAttendance/ProcessXML',
            ],
        ]);

        $requestXML = new \SimpleXMLElement('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"/>');
        $body       = $requestXML->addChild('soap:Body');
        $processXML = $body->addChild('ProcessXML', null, 'https://www.pe-online.org/pe-services/PE_AttendanceElearning/WriteAttendance');
        $processXML->addChild('sXML', htmlspecialchars($xml->asXML()));

        Log::debug('Request XML: ' . $requestXML->asXML());

        try {
            $response = $peClient->post('', [
                'body' => $requestXML->asXML(),
            ]);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            Log::error('Error exporting planned course', ['response' => $response->getBody()->getContents()]);
            throw $e;
        }

        $this->plannedCourse->response = $response->getBody()->getContents();
        $this->plannedCourse->save();

        Log::debug('Response XML: ', ['response' => $response->getBody()->getContents()]);
    }
}
