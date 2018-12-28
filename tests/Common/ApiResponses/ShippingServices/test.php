<?php

function makeacall($id)
{
    $curl = curl_init();

    curl_setopt_array(
        $curl,
        array(
            CURLOPT_URL => "https://api.packlink.com/v1/services/available/$id/details",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => array(
                'Authorization: 17aa2a791ce2a9df0d0b91ccd08baaad0755816aca5dea2298bbb384cf4e3437',
                'Postman-Token: a83d5c6b-109d-4577-911a-ecb7a3ddabf4',
                'cache-control: no-cache'
            ),
        )
    );

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}

$services = array();
foreach (array('IT-IT', 'IT-ES', 'IT-DE', 'IT-FR', 'IT-US') as $p)
{
    $services[] = 'new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails(\'' . $p . '\'))';

    $s = json_decode(file_get_contents("ShippingServiceDetails-$p.json"), true);
    foreach ($s as $service) {
        $services[] = 'new HttpResponse(200, array(), $this->getDemoServiceDetails(' . $service['id'] . '))';
    }
}

echo implode(",\n", $services);
