<?

require __DIR__ . '/vendor/autoload.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

if ($_FILES['file']['tmp_name']) {

    $exportFile = [
        ["Имя", "Ссылка", "ИНН", "ОГРН", "Категория", "Регион", "Арбитражный управляющий", "Адрес", "Номер судебного дела"]
    ];

    if ($xlsx = SimpleXLSX::parse($_FILES['file']['tmp_name'])) {

        $array = $xlsx->rows();

        foreach ($array as $arItem) {
            $id = $arItem[1];

            if ($id !== 0) {
                $number_length = strlen($id);

                array_push($exportFile, request($id, $number_length));
            }
        }

        $xlsxExport = SimpleXLSXGen::fromArray($exportFile);
        $xlsxExport->saveAs('export.xlsx');
        // $xlsxExport->downloadAs('export.xlsx');

        echo json_encode($exportFile);
    }
}


function request($id, $number_length)
{
    $curl = curl_init();

    if ($number_length === 10) {
        $curlUrl = 'https://bankrot.fedresurs.ru/backend/cmpbankrupts?searchString=' . $id . '&isActiveLegalCase=null&limit=15&offset=0';
    } else if ($number_length === 12) {
        $curlUrl = 'https://bankrot.fedresurs.ru/backend/prsnbankrupts?searchString=' . $id . '&isActiveLegalCase=null&limit=15&offset=0';
    }


    curl_setopt_array($curl, array(
        CURLOPT_URL => $curlUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 500,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Referer:https://bankrot.fedresurs.ru',
            'User-Agent:PostmanRuntime/7.29.0',
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $obj = json_decode($response, true);

    if ($obj['pageData'][0]) {
        $link = ($number_length === 10 ? 'https://fedresurs.ru/company/' : 'https://fedresurs.ru/person/') . $obj['pageData'][0]['guid'];


        $file = [

            $number_length === 10 ? $obj['pageData'][0]['name'] : $obj['pageData'][0]['fio'],
            $link,
            $obj['pageData'][0]['inn'],
            $obj['pageData'][0]['ogrn'],
            $obj['pageData'][0]['category'],
            $obj['pageData'][0]['region'],
            $obj['pageData'][0]['arbitrManagerFio'],
            $obj['pageData'][0]['address'],
            $obj['pageData'][0]['lastLegalCase']['number']

        ];
    }

    return $file;
}
