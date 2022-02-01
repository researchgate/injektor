<?php
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injektor;

class SimpleAnnotationReader {

    /**
     * @param string $docComment
     * @return string
     */
    public function getClassFromVarTypeHint($docComment) {
        return $this->getClassFromTypeHint($docComment, '@var');
    }

    /**
     * @param string $docComment
     * @param string $tag
     * @return string mixed
     */
    private function getClassFromTypeHint($docComment, $tag) {
        $matches = array();
        preg_match('/' . $tag . '\s([a-zA-Z0-9\_\\\\\[\\]]+)/', $docComment, $matches);
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
