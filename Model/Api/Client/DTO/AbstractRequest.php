<?php
namespace Billmate\NwtBillmateCheckout\Model\DTO;

abstract class AbstractRequest
{
    /**
     * Ensure we convert to UTF-8
     *
     * @return false|string
     */
    public function toJSON()
    {
        return json_encode(
            $this->utf8ize($this->toArray())
        );
    }

    abstract public function toArray();

    /**
     * @param $mixed
     *
     * @return mixed
     */
    protected function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }

        return $mixed;
    }
}
