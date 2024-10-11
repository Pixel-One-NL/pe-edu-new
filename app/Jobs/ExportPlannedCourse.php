<?php

namespace App\Jobs;

use App\Models\EduframeUser;
use App\Models\PlannedCourse;
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
     * @var PlannedCourse $plannedCourse The external course code
     */
    protected PlannedCourse $plannedCourse;

    /**
     * Create a new job instance.
     */
    public function __construct($plannedCourse)
    {
        $this->plannedCourse = $plannedCourse;
    }

    public function handle(): void
    {
        /**
         * Get the users this way:
         * 1. Get the planned course
         * 2. Get all the meetings of the planned course
         * 3. Get all the attendances of each meeting where the state is 'attended'
         * 4. Get all the users of each attendance, a attendance has one user
         * 5. Group everything, so you only get users that have attended all meetings
         */
        $meetingsCount = $this->plannedCourse->meetings->count();

        $users = $this->plannedCourse->meetings->flatMap(function ($meeting) {
            return $meeting->attendances->where('state', 'attended');
        })
            ->groupBy('enrollment_eduframe_id')
            ->filter(function ($attendances) use ($meetingsCount) {
                return $attendances->count() === $meetingsCount;
            })
            // Now only return the attendances users in one collection
            ->flatMap(function ($attendances) {
                return $attendances->map(function ($attendance) {
                    return $attendance->user;
                });
            })
            ->unique('eduframe_id');

        $xml = new \SimpleXMLElement('<Entry/>');

        $settings = $xml->addChild('Settings');
        $settings->addChild('userID', htmlspecialchars(env('PE_USER_ID')));
        $settings->addChild('userRole', 'EDU');
        $settings->addChild('userKey', htmlspecialchars(env('PE_USER_KEY')));
        $settings->addChild('orgID', htmlspecialchars(env('PE_ORG_ID')));
        $settings->addChild('settingOutput', 1);
        $settings->addChild('emailOutput', htmlspecialchars(env('PE_EMAIL')));
        $settings->addChild('languageID', 1);
        $settings->addChild('defaultLanguageID', 1);

        foreach ($users as $user) {
            // Format the RIZIV number
            $externalPersonId = htmlspecialchars($user->riziv_number);
            $externalPersonId = str_replace(['-', ' '], '', $externalPersonId);
            $externalPersonId = substr($externalPersonId, 1);
            $externalPersonId = substr($externalPersonId, 0, 5);

            $attendanceElement = $xml->addChild('Attendance');
            $attendanceElement->addChild('PECourseID', $this->plannedCourse->pe_course_id);
            $attendanceElement->addChild('externalPersonID', $externalPersonId);
            $attendanceElement->addChild('PEEditionID', $this->plannedCourse->edition_id);
//            $attendanceElement->addChild('PEEditionID', $this->plannedCourse->edition_id);
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $dom->xmlVersion = '1.0';
        $dom->standalone = true;

        $peClient = new \GuzzleHttp\Client([
            'base_uri' => 'https://acc.pe-online.org/pe-services/pe-attendanceelearning/WriteAttendance.asmx',
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => 'https://www.pe-online.org/pe-services/PE_AttendanceElearning/WriteAttendance/ProcessXML',
            ],
            'request.options' => [
                'exceptions' => false,
            ],
            'http_errors' => false,
            'defaults' => [
                'exceptions' => false,
            ],
        ]);

        $requestXML = new \SimpleXMLElement('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"/>');
        $body = $requestXML->addChild('soap:Body');
        $processXML = $body->addChild('ProcessXML', null, 'https://www.pe-online.org/pe-services/PE_AttendanceElearning/WriteAttendance');
        $processXML->addChild('sXML', htmlspecialchars($xml->asXML()));


        $response = $peClient->post('', [
            'body' => $requestXML->asXML(),
        ]);

        $this->plannedCourse->update([
            'exported_at' => now(),
            'export_xml' => $requestXML->asXML(),
            'response' => $response->getBody()->getContents(),
        ]);

        Log::debug('Request XML: ' . $requestXML->asXML());
        Log::debug('Response XML: ', ['response' => $response->getBody()->getContents()]);
    }
}
