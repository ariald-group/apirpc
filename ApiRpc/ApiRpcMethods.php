<?php
    /**
     * ApiRpcMethods
     *
     * Date: 06/05/2019
     * @version   1.0
     * @author    Vitaliy Kurshinov
     * @copyright Ariald Group
     * @link      https://ariald.com
     * @link      https://github.com/kurshinov/ApiRpc
     *
     * @license   GNU General Public License v3.0
     * @license   https://github.com/ariald-group/apirpc/blob/master/LICENSE
     */

    namespace ApiRpc;

    use ReflectionClass;
    use ReflectionException;

    class ApiRpcMethods
    {
        /**
         * Label for method accessibility in the API
         */
        const ANNOTATION = '@ApiRpcMethod';

        /**
         * Class API methods
         *
         * @var object
         */
        private $parentClass;

        /**
         * @var string
         */
        private $method;

        /**
         * @var \ReflectionParameter[]
         */
        private $methodParams;

        /**
         * ApiRpc constructor.
         *
         * @param $parentClass
         */
        function __construct($parentClass)
        {
            $this->parentClass = $parentClass;
        }

        /**
         * Call method
         *
         * @param string $method
         * @param array  $params
         *
         * @return mixed
         * @throws ApiRpcException
         *
         * @since 1.0
         */
        public function call(string $method, array $params)
        {
            if ($this->isMethodAvailable($method))
            {
                $this->method = $method;

                if ($this->isValidParamsName($params))
                {
                    return $this->callParentMethod($this->getSortParams($params));
                }
            }
        }

        /**
         * getAllApiMethods
         *
         * @return array
         * @throws ApiRpcException
         *
         * @since 1.0
         */
        public function getAllApiMethods() : array
        {
            $methods = [];

            try
            {
                $reflection = new ReflectionClass($this->parentClass);

                foreach ($reflection->getMethods() as $methodObj)
                {
                    if ($this->isRpcMethod($methodObj))
                    {
                        $methodParams = $methodObj->getParameters();
                        $tmpParams = [];

                        foreach ($methodParams as $methodParamObj)
                        {
                            $tmpParams[] = array_merge([
                                'name'     => $methodParamObj->name,
                                'type'     => $methodParamObj->getType()->getName(),
                                'optional' => $methodParamObj->isOptional()
                            ],
                                $methodParamObj->isOptional() ? ['default' => $methodParamObj->getDefaultValue()] : []
                            );
                        }

                        $methods[$methodObj->name]['params'] = $tmpParams;
                    }
                }
            } catch (ReflectionException $e)
            {
                throw new ApiRpcException($e->getMessage(), $e->getCode());
            }

            return $methods;
        }

        /**
         * Is method included in class $this->parentClass
         *
         * @param string $method
         *
         * @return bool
         * @throws ApiRpcException
         * @since 1.0
         */
        private function isMethodAvailable(string $method) : bool
        {
            if (!array_key_exists($method, $this->getMethods()))
            {
                throw new ApiRpcException('Method not defined', 1);
            }

            return true;
        }

        /**
         * Is method included in class $this->parentClass
         *
         * @param object $methodObj
         *
         * @return string
         *
         * @since 1.0
         */
        private function isRpcMethod(object $methodObj) : string
        {
            return strstr($methodObj->getDocComment(), self::ANNOTATION);
        }

        /**
         * Check the names of incoming parameters
         *
         * @param array $params
         *
         * @return bool
         * @throws ApiRpcException
         *
         * @since 1.0
         */
        private function isValidParamsName(array $params) : bool
        {
            $tmpMethodParams = [];
            $methodParams = $this->getMethodParameters($this->method);

            foreach ($methodParams as $methodParamObj)
            {
                $tmpMethodParams[] = $methodParamObj->name;
            }

            foreach ($params as $param => $value)
            {
                if (!in_array($param, $tmpMethodParams))
                {
                    throw new ApiRpcException(sprintf('Parameter (%s) not found', $param), 2);
                }
            }

            $this->methodParams = $methodParams;

            return true;
        }

        /**
         * Get class methods $this->parentClass
         *
         * @return array
         * @throws ApiRpcException
         *
         * @since 1.0
         */
        private function getMethods() : array
        {
            $methods = [];

            try
            {
                $reflection = new ReflectionClass($this->parentClass);

                foreach ($reflection->getMethods() as $methodObj)
                {
                    if ($this->isRpcMethod($methodObj))
                    {
                        $methods[$methodObj->name] = $methodObj->getParameters();
                    }
                }
            } catch (ReflectionException $e)
            {
                throw new ApiRpcException($e->getMessage(), $e->getCode());
            }

            return $methods;
        }

        /**
         * Getting method parameters $this->parentClass
         *
         * @param string $method
         *
         * @return \ReflectionParameter[]
         * @throws ApiRpcException
         *
         * @since 1.0
         */
        private function getMethodParameters(string $method)
        {
            try
            {
                $reflection = new ReflectionClass($this->parentClass);

                foreach ($reflection->getMethods() as $methodObj)
                {
                    if ($methodObj->name == $method)
                    {
                        return $methodObj->getParameters();
                    }
                }
            } catch (ReflectionException $e)
            {
                throw new ApiRpcException($e->getMessage(), $e->getCode());
            }
        }

        /**
         * Sort incoming parameters according to their instructions in the methods $this->parentClass
         *
         * @param array $params
         *
         * @return array
         * @throws ApiRpcException
         *
         * @since 1.0
         */
        private function getSortParams(array $params) : array
        {
            $paramsSort = [];

            try
            {
                foreach ($this->methodParams as $methodParamObj)
                {
                    // If the parameter is required
                    if (!$methodParamObj->isOptional())
                    {
                        if (!array_key_exists($methodParamObj->name, $params))
                        {
                            throw new ApiRpcException(sprintf('Required parameter (%s) not found', $methodParamObj->name), 3);
                        }
                    }
                    else
                    {
                        // If the parameter is optional and is not passed, then we assign the default value to it
                        if (!array_key_exists($methodParamObj->name, $params))
                        {
                            $paramsSort[$methodParamObj->name] = $methodParamObj->getDefaultValue();
                            continue;
                        }
                    }

                    $paramName = $methodParamObj->name;
                    $paramValue = $params[$paramName];

                    if ($this->isValidParamType($methodParamObj->getType()->getName(), $paramValue))
                    {
                        $paramsSort[$paramName] = $paramValue;
                        unset($params[$paramName]);
                        continue;
                    }

                    throw new ApiRpcException(sprintf('Parameter (%s) type must be (%s)', $methodParamObj->name, $methodParamObj->getType()->getName()), 4);
                }
            } catch (ReflectionException $e)
            {
                throw new ApiRpcException($e->getMessage(), $e->getCode());
            }

            return $paramsSort;
        }

        /**
         * Validation type parameter
         *
         * @param string $needType
         * @param        $param
         *
         * @return bool
         *
         * @since 1.0
         */
        private function isValidParamType(string $needType, $param) : bool
        {
            $paramType = gettype($param);
            $paramType = str_replace("integer", "int", $paramType);
            $paramType = str_replace("boolean", "bool", $paramType);

            return ($needType === $paramType);
        }

        /**
         * Call method $this->parentClass
         *
         * @param array $params
         *
         * @return mixed
         *
         * @since 1.0
         */
        private function callParentMethod(array $params)
        {
            return call_user_func_array([$this->parentClass, $this->method], $params);
        }

    }