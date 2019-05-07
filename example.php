<?php
    /**
     * JSON-RPC information
     *
     * @link https://en.wikipedia.org/wiki/JSON-RPC
     */

    require __DIR__ . '/vendor/autoload.php';

    use ApiRpc\ApiRpcException;
    use ApiRpc\ApiRpcJsonParser;
    use ApiRpc\ApiRpcMethods;

    /**
     * Class testApi
     */
    class testApi
    {
        /**
         * @ApiRpcMethod
         *
         * @param string $varStr
         * @param array  $varArray
         * @param int    $varInt
         * @param bool   $varBool
         *
         * @return array
         */
        public function testMethod(string $varStr, array $varArray, int $varInt = 1, bool $varBool = true) : array
        {
            return [
                'success'  => true,
                'varStr'   => $varStr . '_apirpc',
                'varArray' => array_merge($varArray, ['ApiRpc']),
                'varInt'   => $varInt * 2,
                'varBool'  => $varBool
            ];
        }

        /**
         * @ApiRpcMethod
         *
         * @param int    $amount
         * @param string $str
         *
         * @return array
         */
        public function testMethod2(int $amount, string $str = 'Hello') : array
        {
            return [
                'amount' => $amount,
                'str'    => $str
            ];
        }
    }


    // Example call api

    // get json-rpc
    $json = json_encode([
            'jsonrpc' => '2.0',
            'method'  => 'testMethod',
            'params'  => [
                'varArray' => [1, 2],
                'varBool'  => false,
                'varStr'   => 'test',
                'varInt'   => 2
            ],
            'id'      => md5(uniqid())
        ]
    );

    echo PHP_EOL . 'Request: ' . $json . PHP_EOL;

    try
    {
        $ApiRpc = new ApiRpcMethods(new testApi());
        $ApiRpcJson = new ApiRpcJsonParser($json);

        $ApiResult = $ApiRpc->call(
            $ApiRpcJson->getMethod(),
            $ApiRpcJson->getParams()
        );

        if (is_array($ApiResult))
        {
            echo PHP_EOL . 'Response: ' . $ApiRpcJson->getResponse($ApiResult) . PHP_EOL;
        }

        echo PHP_EOL . 'Print all api methods' . PHP_EOL . PHP_EOL;

        print_r($ApiRpc->getAllApiMethods());

    } catch (ApiRpcException $e)
    {
        die("Code: " . $e->getCode() . " | Message: " . $e->getMessage());
    }