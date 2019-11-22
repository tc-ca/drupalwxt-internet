<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* profiles/wxt/modules/custom/wxt_ext/wxt_ext_layout/templates/homepage.html.twig */
class __TwigTemplate_9eb5bae3de530c9e52ca3a5bdf674e22f64bac69d71e02d01d7fed3bc7e09eda extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["if" => 30];
        $filters = ["escape" => 27];
        $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->getSourceContext());

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 26
        echo "<div class=\"row\">
  <";
        // line 27
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["wrapper"] ?? null)), "html", null, true);
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["attributes"] ?? null)), "html", null, true);
        echo ">
    ";
        // line 28
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["title_suffix"] ?? null), "contextual_links", [])), "html", null, true);
        echo "

    ";
        // line 30
        if ($this->getAttribute(($context["top"] ?? null), "content", [])) {
            // line 31
            echo "    <";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top"] ?? null), "wrapper", [])), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top"] ?? null), "attributes", [])), "html", null, true);
            echo ">
      ";
            // line 32
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top"] ?? null), "content", [])), "html", null, true);
            echo "
    </";
            // line 33
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top"] ?? null), "wrapper", [])), "html", null, true);
            echo ">
    ";
        }
        // line 35
        echo "
    ";
        // line 36
        if ($this->getAttribute(($context["top_left"] ?? null), "content", [])) {
            // line 37
            echo "    <";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top_left"] ?? null), "wrapper", [])), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top_left"] ?? null), "attributes", [])), "html", null, true);
            echo ">
      ";
            // line 38
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top_left"] ?? null), "content", [])), "html", null, true);
            echo "
    </";
            // line 39
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top_left"] ?? null), "wrapper", [])), "html", null, true);
            echo ">
    ";
        }
        // line 41
        echo "
    ";
        // line 42
        if ($this->getAttribute(($context["top_right"] ?? null), "content", [])) {
            // line 43
            echo "    <";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top_right"] ?? null), "wrapper", [])), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top_right"] ?? null), "attributes", [])), "html", null, true);
            echo ">
      ";
            // line 44
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top_right"] ?? null), "content", [])), "html", null, true);
            echo "
    </";
            // line 45
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["top_right"] ?? null), "wrapper", [])), "html", null, true);
            echo ">
    ";
        }
        // line 47
        echo "
    ";
        // line 48
        if ($this->getAttribute(($context["middle"] ?? null), "content", [])) {
            // line 49
            echo "    <";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["middle"] ?? null), "wrapper", [])), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["middle"] ?? null), "attributes", [])), "html", null, true);
            echo ">
      ";
            // line 50
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["middle"] ?? null), "content", [])), "html", null, true);
            echo "
    </";
            // line 51
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["middle"] ?? null), "wrapper", [])), "html", null, true);
            echo ">
    ";
        }
        // line 53
        echo "
    <div class=\"wxt-eqht\">
      ";
        // line 55
        if ($this->getAttribute(($context["bottom_left"] ?? null), "content", [])) {
            // line 56
            echo "      <";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_left"] ?? null), "wrapper", [])), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_left"] ?? null), "attributes", [])), "html", null, true);
            echo ">
        ";
            // line 57
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_left"] ?? null), "content", [])), "html", null, true);
            echo "
      </";
            // line 58
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_left"] ?? null), "wrapper", [])), "html", null, true);
            echo ">
      ";
        }
        // line 60
        echo "
      ";
        // line 61
        if ($this->getAttribute(($context["bottom_middle"] ?? null), "content", [])) {
            // line 62
            echo "      <";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_middle"] ?? null), "wrapper", [])), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_middle"] ?? null), "attributes", [])), "html", null, true);
            echo ">
        ";
            // line 63
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_middle"] ?? null), "content", [])), "html", null, true);
            echo "
      </";
            // line 64
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_middle"] ?? null), "wrapper", [])), "html", null, true);
            echo ">
      ";
        }
        // line 66
        echo "    </div>

    ";
        // line 68
        if ($this->getAttribute(($context["bottom_right"] ?? null), "content", [])) {
            // line 69
            echo "    <";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_right"] ?? null), "wrapper", [])), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_right"] ?? null), "attributes", [])), "html", null, true);
            echo ">
      ";
            // line 70
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_right"] ?? null), "content", [])), "html", null, true);
            echo "
    </";
            // line 71
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom_right"] ?? null), "wrapper", [])), "html", null, true);
            echo ">
    ";
        }
        // line 73
        echo "
    ";
        // line 74
        if ($this->getAttribute(($context["bottom"] ?? null), "content", [])) {
            // line 75
            echo "    <";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom"] ?? null), "wrapper", [])), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom"] ?? null), "attributes", [])), "html", null, true);
            echo ">
      ";
            // line 76
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom"] ?? null), "content", [])), "html", null, true);
            echo "
    </";
            // line 77
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["bottom"] ?? null), "wrapper", [])), "html", null, true);
            echo ">
    ";
        }
        // line 79
        echo "
  </";
        // line 80
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["wrapper"] ?? null)), "html", null, true);
        echo ">
</div>
";
    }

    public function getTemplateName()
    {
        return "profiles/wxt/modules/custom/wxt_ext/wxt_ext_layout/templates/homepage.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  230 => 80,  227 => 79,  222 => 77,  218 => 76,  212 => 75,  210 => 74,  207 => 73,  202 => 71,  198 => 70,  192 => 69,  190 => 68,  186 => 66,  181 => 64,  177 => 63,  171 => 62,  169 => 61,  166 => 60,  161 => 58,  157 => 57,  151 => 56,  149 => 55,  145 => 53,  140 => 51,  136 => 50,  130 => 49,  128 => 48,  125 => 47,  120 => 45,  116 => 44,  110 => 43,  108 => 42,  105 => 41,  100 => 39,  96 => 38,  90 => 37,  88 => 36,  85 => 35,  80 => 33,  76 => 32,  70 => 31,  68 => 30,  63 => 28,  58 => 27,  55 => 26,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "profiles/wxt/modules/custom/wxt_ext/wxt_ext_layout/templates/homepage.html.twig", "/Users/joelb/Sites/wxt83001/html/profiles/wxt/modules/custom/wxt_ext/wxt_ext_layout/templates/homepage.html.twig");
    }
}
