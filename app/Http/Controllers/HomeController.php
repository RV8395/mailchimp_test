<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function importContacts(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlx,xls'
        ]);

        if ($request->file()) {
            $name = time() . '_' . $request->file->getClientOriginalName();
            $filePath = $request->file('file')->storeAs('uploads', $name, 'public');

            $fileD = fopen('storage/' . $filePath, "r");
            $column = fgetcsv($fileD);
            while (!feof($fileD)) {
                $rowData[] = fgetcsv($fileD);
            }
            unlink(storage_path('app/public/' . $filePath));

            $users = $this->prepareData($rowData);
            $operations = $this->prepareOperationsForAddContacts($users);
            $response = $this->syncData($operations);

            return back()
                ->with('success', 'File has synced with mailchimp successfully.');
        }
    }

    public function updateContacts(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlx,xls'
        ]);

        if ($request->file()) {
            $name = time() . '_' . $request->file->getClientOriginalName();
            $filePath = $request->file('file')->storeAs('uploads', $name, 'public');

            $fileD = fopen('storage/' . $filePath, "r");
            $column = fgetcsv($fileD);
            while (!feof($fileD)) {
                $rowData[] = fgetcsv($fileD);
            }
            unlink(storage_path('app/public/' . $filePath));

            $operations = $this->prepareOperationsForUpdateContacts($rowData);
            $response = $this->syncData($operations);

            $operations = $this->syncTags($rowData);
            $response = $this->syncData($operations);

            return back()
                ->with('success', 'File has synced with mailchimp successfully.');
        }
    }

    public function syncTagsOfMembers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlx,xls'
        ]);

        if ($request->file()) {
            $name = time() . '_' . $request->file->getClientOriginalName();
            $filePath = $request->file('file')->storeAs('uploads', $name, 'public');

            $fileD = fopen('storage/' . $filePath, "r");
            $column = fgetcsv($fileD);
            while (!feof($fileD)) {
                $rowData[] = fgetcsv($fileD);
            }
            unlink(storage_path('app/public/' . $filePath));

            $operations = $this->syncTags($rowData);
            $response = $this->syncData($operations);

            return back()
                ->with('success', 'File has synced with mailchimp successfully.');
        }
    }

    public function getListOfMembers()
    {
        $client = new \MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => config('constants.apiKey', '06fb76c7ec26d4f6055ec2d475d9a2f9-us5'),
            'server' => config('constants.server', 'us5'),
        ]);

        $response = $client->lists->getListMembersInfo(config('constants.listId', 'f18bc7f360'), $fields = null, $exclude_fields = null, $count = '1000');

        $fileName = 'contacts.csv';

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Id', 'Email', 'First Name', 'Last Name', 'Status', 'Birthday');

        $callback = function () use ($response, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($response->members as $member) {
                $row['Id']  = $member->id;
                $row['Email']    = $member->email_address;
                $row['FirstName']    = $member->merge_fields->FNAME;
                $row['LastName']  = $member->merge_fields->LNAME;
                $row['Status']  = $member->status;
                $row['Birthday']  = $member->merge_fields->BIRTHDAY;

                fputcsv($file, array($row['Id'], $row['Email'], $row['FirstName'], $row['LastName'], $row['Status'], $row['Birthday']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ----------------------------------------------------- PRIVATE functions
    private function prepareData($rowData)
    {
        $contacts = array();
        foreach ($rowData as $key => $data) {
            if (isset($data[2]) && !empty($data[2])) {
                $contacts[] = [
                    'id' => (string) ($key + 1),
                    'email' => $data[2],
                    'first_name' => $data[0],
                    'last_name' => $data[1],
                ];
            }
        }

        return $contacts;
    }

    private function prepareOperationsForAddContacts($users)
    {
        $operations = [];
        foreach ($users as $user) {
            $operation = [
                'method' => 'POST',
                'path' => "/lists/" . config('constants.listId', 'f18bc7f360') . "/members",
                'operation_id' => $user['id'],
                'body' => json_encode([
                    "email_address" => $user['email'],
                    "merge_fields" => [
                        "FNAME" => $user['first_name'],
                        "LNAME" => $user['last_name'],
                    ],
                    "status" => "subscribed"
                ])
            ];
            array_push($operations, $operation);
        }

        return $operations;
    }

    private function prepareOperationsForUpdateContacts($rowData)
    {
        $contacts = array();
        foreach ($rowData as $key => $data) {
            if (isset($data[2]) && !empty($data[2])) {
                $contacts[] = [
                    'id' => (string) ($key + 1),
                    'email' => $data[2],
                    'first_name' => $data[0],
                    'last_name' => $data[1],
                    'address' => $data[3],
                    'phone' => $data[4],
                    'title' => $data[5],
                    'tags' => $data[6],
                    'dob' => $this->validateDate($data[7]) ? \Carbon\Carbon::parse($data[7])->format('m/d') : '',
                    'street_address' => $data[8],
                    'city' => $data[9],
                    'state' => $data[10],
                    'zip' => $data[11],
                    'country' => $data[12],
                    'country_full' => $data[13],
                    'custom_field_1' => $data[14],
                    'custom_field_2' => $data[15],
                ];
            }
        }

        $operations = [];
        foreach ($contacts as $user) {
            $operation = [
                'method' => 'PATCH',
                'path' => "/lists/" . config('constants.listId', 'f18bc7f360') . "/members/" . md5(strtolower($user['email'])),
                'operation_id' => $user['id'],
                'body' => json_encode([
                    "email_address" => $user['email'],
                    "merge_fields" => [
                        "FNAME" => $user['first_name'],
                        "LNAME" => $user['last_name'],
                        "ADDRESS" => $user['address'],
                        "PHONE" => $user['phone'],
                        "BIRTHDAY" => $user['dob'],
                        "TITLE" => $user['title'],
                        "CITY" => $user['city'],
                        "STATE" => $user['state'],
                        "ZIP" => $user['zip'],
                        "COUNTRY" => $user['country'],
                        "COUNTRYFUL" => $user['country_full'],
                        "CUSTOM1" => $user['custom_field_1'],
                        "CUSTOM2" => $user['custom_field_2'],
                    ]
                ])
            ];
            array_push($operations, $operation);
        }

        return $operations;
    }

    private function syncTags($rowData)
    {
        $contacts = array();
        foreach ($rowData as $key => $data) {
            if (isset($data[2]) && !empty($data[2])) {
                $tags = array();
                $existTags = $this->getTags($data[2]);
                $arr = explode(',', $data[6]);
                $arr = array_unique(array_map('trim', $arr));

                if (!empty($arr)) {
                    foreach ($arr as $tag) {
                        if (!empty($tag)) {
                            $tags[] = [
                                'name' => $tag,
                                'status' => 'active'
                            ];
                        }
                    }

                    foreach ($existTags as $tag) {
                        if (!in_array($tag, $arr)) {
                            $tags[] = [
                                'name' => $tag,
                                'status' => 'inactive'
                            ];
                        }
                    }
                }

                $contacts[] = [
                    'id' => (string) ($key + 1),
                    'email' => $data[2],
                    'tags' => $tags,
                ];
            }
        }

        $operations = [];
        foreach ($contacts as $user) {
            $operation = [
                'method' => 'POST',
                'path' => "/lists/" . config('constants.listId', 'f18bc7f360') . "/members/" . md5(strtolower($user['email'])) . "/tags",
                'operation_id' => $user['id'],
                'body' => json_encode([
                    "tags" => $user['tags'],
                ])
            ];
            array_push($operations, $operation);
        }

        return $operations;
    }

    private function getTags($email)
    {
        $client = new \MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => config('constants.apiKey', '06fb76c7ec26d4f6055ec2d475d9a2f9-us5'),
            'server' => config('constants.server', 'us5'),
        ]);

        $response = $client->lists->getListMemberTags(config('constants.listId', 'f18bc7f360'), md5(strtolower($email)));
        $tags = array();

        foreach ($response->tags as $tag) {
            $tags[] = $tag->name;
        }

        return $tags;
    }

    private function syncData($operations)
    {
        $client = new \MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => config('constants.apiKey', '06fb76c7ec26d4f6055ec2d475d9a2f9-us5'),
            'server' => config('constants.server', 'us5'),
        ]);

        $response = $client->batches->start([
            "operations" => $operations,
        ]);

        return $response;
    }

    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    // ----------------------------------------------------- PRIVATE functions



    // ----------------------------------------------------- R & D methods
    public function testApi()
    {
        $this->batchOperationsTesting();
        return 1;
        $client = new \MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => '06fb76c7ec26d4f6055ec2d475d9a2f9-us5',
            'server' => 'us5',
        ]);

        // $lists = $client->lists->getAllLists();

        // $addMember = $client->lists->addListMember("f18bc7f360", [
        //     "email_address" => "test102@gmail.com",
        //     "first_name" => "Test",
        //     "last_name" => "102",
        //     "status" => "subscribed",
        // ]);

        // $addOrUpdateMember = $client->lists->setListMember("f18bc7f360", md5('test102@gmail.com'), [
        //     "email_address" => "test102@gmail.com",
        //     "status_if_new" => "subscribed",
        //     "merge_fields" => [
        //         "FNAME" => "Test",
        //         "LNAME" => "1022",
        //     ]
        // ]);

        // $response = $client->lists->getListMembersInfo("f18bc7f360");

        // dd($lists, $response);
        // dd($lists, $addOrUpdateMember, $response);
    }

    public function batchOperationsTesting()
    {
        $client = new \MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => '06fb76c7ec26d4f6055ec2d475d9a2f9-us5',
            'server' => 'us5',
        ]);

        $list_id = "f18bc7f360";

        $users = [
            [
                'id' => '1',
                'email' => 'test201@gmail.com',
                'first_name' => 'Test',
                'last_name' => '201',
            ],
            [
                'id' => '2',
                'email' => 'test202@gmail.com',
                'first_name' => 'Test',
                'last_name' => '202',
            ],
        ];

        $operations = [];
        foreach ($users as $user) {
            $operation = [
                'method' => 'POST',
                'path' => "/lists/$list_id/members",
                'operation_id' => $user['id'],
                'body' => json_encode([
                    "email_address" => $user['email'],
                    "merge_fields" => [
                        "FNAME" => $user['first_name'],
                        "LNAME" => $user['last_name'],
                    ],
                    "status" => "subscribed"
                ])
            ];
            array_push($operations, $operation);
        }
        dd($operations);
        $response = $client->batches->start([
            "operations" => $operations,
        ]);
        dd($response);
    }

    public function bulkImportTesting()
    {
        $mailchimp = new \MailchimpMarketing\ApiClient();
        $mailchimp->setConfig([
            'apiKey' => '06fb76c7ec26d4f6055ec2d475d9a2f9-us5',
            'server' => 'us5',
        ]);

        $list_id = "f18bc7f360";

        $users = [
            [
                'id' => '1',
                'email' => 'test201@gmail.com',
                'first_name' => 'Test',
                'last_name' => '201',
            ],
            [
                'id' => '2',
                'email' => 'test202@gmail.com',
                'first_name' => 'Test',
                'last_name' => '202',
            ],
        ];

        $operations = [];
        foreach ($users as $user) {
            $operation = [
                'method' => 'POST',
                'path' => "/lists/$list_id/members",
                'operation_id' => $user['id'],
                'body' => json_encode([
                    "email_address" => $user['email'],
                    "merge_fields" => [
                        "FNAME" => $user['first_name'],
                        "LNAME" => $user['last_name'],
                    ],
                    "status" => "subscribed"
                ])
            ];
            array_push($operations, $operation);
        }

        try {
            $response = $mailchimp->batches->start($operations);
            dd($response);
        } catch (\MailchimpMarketing\ApiException $e) {
            dd($e->getMessage());
        }
    }
    // ----------------------------------------------------- R & D methods
}
