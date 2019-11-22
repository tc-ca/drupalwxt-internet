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

/* modules/contrib/charts/modules/charts_blocks/templates/charts-block.html.twig */
class __TwigTemplate_f4ff8fe586a885da376a204942faea4a5f53dc8242bf481c47a9311602b56843 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["set" => 1, "if" => 4];
        $filters = ["escape" => 2];
        $functions = ["attach_library" => 2];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if'],
                ['escape'],
                ['attach_library']
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
        // line 1
        list($context["library"], $context["height"], $context["width"], $context["height_units"], $context["width_units"]) =         [((("charts_" . $this->sandbox->ensureToStringAllowed(($context["library"] ?? null))) . "/") . $this->sandbox->ensureToStringAllowed(($context["library"] ?? null))), $this->getAttribute(($context["options"] ?? null), "height", []), $this->getAttribute(($context["options"] ?? null), "width", []), $this->getAttribute(($context["options"] ?? null), "height_units", []), $this->getAttribute(($context["options"] ?? null), "width_units", [])];
        // line 2
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->env->getExtension('Drupal\Core\Template\TwigExtension')->attachLibrary($this->sandbox->ensureToStringAllowed(($context["library"] ?? null))), "html", null, true);
        echo "
<div ";
        // line 3
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["attributes"] ?? null)), "html", null, true);
        echo " ";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["content_attributes"] ?? null)), "html", null, true);
        echo "
        style=\"";
        // line 4
        if ( !twig_test_empty(($context["width"] ?? null))) {
            echo "width:";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["width"] ?? null)), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["width_units"] ?? null)), "html", null, true);
            echo ";";
        }
        if ( !twig_test_empty(($context["height"] ?? null))) {
            echo "height:";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["height"] ?? null)), "html", null, true);
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["height_units"] ?? null)), "html", null, true);
            echo ";";
        }
        echo "\"></div>
";
    }

    public function getTemplateName()
    {
        return "modules/contrib/charts/modules/charts_blocks/templates/charts-block.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  67 => 4,  61 => 3,  57 => 2,  55 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "modules/contrib/charts/modules/charts_blocks/templates/charts-block.html.twig", "/Users/joelb/Sites/wxt83001/html/modules/contrib/charts/modules/charts_blocks/templates/charts-block.html.twig");
    }
}
