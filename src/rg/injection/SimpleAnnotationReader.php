<?php
/*
 * This file is part of rg\injection.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injection;

class SimpleAnnotationReader {

    /**
     * @param string $docComment
     * @return string
     * @throws InjectionException
     */
    public function getClassFromVarTypeHint($docComment) {
        return trim($this->getClassFromTypeHint($docComment, '@var'), '\\');
    }

    /**
     * @param string $docComment
     * @param string $tag
     * @return string mixed
     * @throws InjectionException
     */
    private function getClassFromTypeHint($docComment, $tag) {
        $matches = array();
        preg_match('/' . $tag . '\s([a-zA-Z0-9\\\\\[\\]]+)/', $docComment, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }
        return null;
    }

    /**
     * @param string $docComment
     * @param string $agumentName
     * @param string $annotation
     * @return array
     */
    public function getParamsFromTypeHint($docComment, $agumentName, $annotation) {
        $matches = array();

        $pattern = '@' . $annotation . '(\s+[a-zA-Z0-9\\\]*)?';
        if ($annotation === 'param') {
            $pattern .= '\s+\$' . preg_quote($agumentName, '/');
        }
        $pattern .= '(\s+(?P<parameters>{.+}))?';

        preg_match('/' . $pattern . '/', $docComment, $matches);
        if (isset($matches['parameters'])) {
            return json_decode($matches['parameters'], true) ? : array();
        }
        return array();
    }

}