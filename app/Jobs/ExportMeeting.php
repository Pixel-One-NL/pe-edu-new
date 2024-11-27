<?php

namespace App\Jobs;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExportMeeting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Meeting The meeting to export
     */
    protected $meeting;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    /**
     * Get the XML request for the meeting export
     *
     * @return \SimpleXMLElement
     */
    public function getXmlRequest(): \SimpleXMLElement
    {
        /**
         * Make the XML request for the PE-Online SOAP API
         * Example:
         * <?xml version="1.0" standalone="yes"?>
         * <Entry>
         *     <Settings>
         *         <userID>[wordt u aangeleverd]</userID>
         *         <userRole>EDU</userRole>
         *         <userKey>[wordt u aangeleverd]</userKey>
         *         <orgID>[organisatie uit org_lijst.pdf]</orgID>
         *         <settingOutput>1</settingOutput>
         *         <emailOutput>s.peek@bestaatniet.nl</emailOutput>
         *         <languageID>1</languageID>
         *         <defaultLanguageID>1</defaultLanguageID>
         *     </Settings>
         *     <Attendance>
         *         <PECourseID>654321</PECourseID>
         *         <externalPersonID>12345678901</externalPersonID>
         *         <PEmoduleID>12345</PEmoduleID>
         *         <endDate>2021-03-09T13:08:13.9422087+02:00</endDate>
         *     <Attendance>
         * </Entry>
         */

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

        foreach($this->meeting->attendances as $attendance) {
            $externalPersonId = htmlspecialchars($attendance->enrollment->user->RizivNumber);
            $externalPersonId = str_replace(['-', ' '], '', $externalPersonId);
            $externalPersonId = substr($externalPersonId, 1);
            $externalPersonId = substr($externalPersonId, 0, 5);

            $attendanceElement = $xml->addChild('Attendance');
            $attendanceElement->addChild('externalCourseID', $this->meeting->plannedCourse->course->code);
            $attendanceElement->addChild('externalPersonID', $externalPersonId);
            // $attendanceElement->addChild('PEEditionID', $this->meeting->pe_code);
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $dom->xmlVersion = '1.0';
        $dom->standalone = true;

        return $xml;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $peClient = new \GuzzleHttp\Client([
            'base_uri' => 'https://www.pe-online.org/pe-services/pe-attendanceelearning/WriteAttendance.asmx',
            'headers'  => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction'   => 'https://www.pe-online.org/pe-services/PE_AttendanceElearning/WriteAttendance/ProcessXML',
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
        $body       = $requestXML->addChild('soap:Body');
        $processXML = $body->addChild('ProcessXML', null, 'https://www.pe-online.org/pe-services/PE_AttendanceElearning/WriteAttendance');
        $processXML->addChild('sXML', htmlspecialchars($this->getXmlRequest()->asXML()));

        try {
            $response = $peClient->post('', [
                'body' => $requestXML->asXML(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to export meeting 1 ' . $this->meeting->id, ['exception' => $e]);
            return;
        } catch (\Throwable $e) {
            Log::error('Failed to export meeting 2 ' . $this->meeting->id, ['exception' => $e]);
            return;
        } catch (\Error $e) {
            Log::error('Failed to export meeting 3 ' . $this->meeting->id, ['exception' => $e]);
            return;
        } catch (RequestException $e) {
            Log::error('Failed to export meeting 4 ' . $this->meeting->id, ['exception' => $e]);
            return;
        }

        Log::info('Exported meeting ' . $this->meeting->id);
        Log::debug('Request XML: ' . $requestXML->asXML());
        Log::debug('Response XML: ', ['response' => $response->getBody()->getContents()]);

        $this->meeting->update([
            'exported'    => true,
            'exported_at' => now(),
            'export_xml'  => $requestXML->asXML(),
        ]);
    }
}
