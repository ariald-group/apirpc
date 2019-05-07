<?php
    /**
     * ApiRpcJsonParser
     *
     * Date: 06/05/2019
     * @version   1.0.1
     * @author    Vitaliy Kurshinov
     * @copyright Ariald Group
     * @link      https://ariald.com
     * @link      https://github.com/ariald-group/apirpc
     *
     * @license   GNU General Public License v3.0
     * @license   https://github.com/ariald-group/apirpc/blob/master/LICENSE
     */

    namespace ApiRpc;

    class ApiRpcJsonParser
    {
        /**
         * @var object Object json
         */
        private $jsonObject;

        /**
         * ApiRpcJsonParser constructor.
         *
         * @param string $json
         *
         * @throws ApiRpcException
         */
        function __construct(string $json)
        {
            $this->validationJsonRpcFormat($json);
        }

        /**
         * Get version
         *
         * @return string
         */
        public function getVersion() : string
        {
            return $this->jsonObject->jsonrpc;
        }

        /**
         * Get method
         *
         * @return string
         */
        public function getMethod() : string
        {
            return $this->jsonObject->method;
        }

        /**
         * Get params
         *
         * @return array
         */
        public function getParams() : array
        {
            return (array) $this->jsonObject->params;
        }

        /**
         * Get id
         *
         * @return string
         */
        public function getId() : string
        {
            return $this->jsonObject->id;
        }

        /**
         * Response JSON-RPC
         *
         * @param array $params
         *
         * @return string
         */
        public function getResponse(array $params) : string
        {
            $jsonRpc = [
                'jsonrpc' => $this->getVersion(),
                'method'  => $this->getMethod(),
                'params'  => $params,
                'id'      => $this->getId()
            ];

            return json_encode($jsonRpc, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
        }

        /**
         * Validation JSON-RPC
         *
         * @param string $jsonRpc
         *
         * @return bool
         * @throws ApiRpcException
         */
        private function validationJsonRpcFormat(string $jsonRpc)
        {
            $param = '';

            if (!is_object($this->jsonObject = json_decode($jsonRpc)))
            {
                throw new ApiRpcException('Json format is not valid');
            }
            elseif (!isset($this->jsonObject->id))
            {
                $param = 'id';
            }
            elseif (!isset($this->jsonObject->jsonrpc))
            {
                $param = 'jsonrpc';
            }
            elseif (!isset($this->jsonObject->method))
            {
                $param = 'method';
            }
            elseif (!isset($this->jsonObject->params))
            {
                $param = 'params';
            }

            if (!empty($param))
            {
                throw new ApiRpcException(sprintf('Parameter (%s) not found', $param));
            }

            return true;
        }

    }