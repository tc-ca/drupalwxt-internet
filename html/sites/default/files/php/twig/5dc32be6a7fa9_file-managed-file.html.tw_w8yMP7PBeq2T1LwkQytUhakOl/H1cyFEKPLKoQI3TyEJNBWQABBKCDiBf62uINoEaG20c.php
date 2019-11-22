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

/* themes/contrib/claro/templates/content-edit/file-managed-file.html.twig */
class __TwigTemplate_892db8c3445121294747cfc881902bab674fbe2c36f830bc26f51fdc54ff6f3d extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["set" => 21, "if" => 35];
        $filters = ["escape" => 30, "without" => 32];
        $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if'],
                ['escape', 'without'],
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
        // line 21
        $context["classes"] = [0 => "js-form-managed-file", 1 => "form-managed-file", 2 => ((        // line 24
($context["multiple"] ?? null)) ? ("is-multiple") : ("is-single")), 3 => ((        // line 25
($context["upload"] ?? null)) ? ("has-upload") : ("no-upload")), 4 => ((        // line 26
($context["has_value"] ?? null)) ? ("has-value") : ("no-value")), 5 => ((        // line 27
($context["has_meta"] ?? null)) ? ("has-meta") : ("no-meta"))];
        // line 30
        echo "<div";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["attributes"] ?? null), "addClass", [0 => ($context["classes"] ?? null)], "method")), "html", null, true);
        echo ">
  <div class=\"form-managed-file__main\">
    ";
        // line 32
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->env->getExtension('Drupal\Core\Template\TwigExtension')->withoutFilter($this->sandbox->ensureToStringAllowed(($context["element"] ?? null)), "display", "description"), "html", null, true);
        echo "
  </div>

  ";
        // line 35
        if (($context["has_meta"] ?? null)) {
            // line 36
            echo "    <div class=\"form-managed-file__meta-wrapper\">
      <div class=\"form-managed-file__meta\">
        <div class=\"form-managed-file__meta-items\">
          ";
            // line 39
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["element"] ?? null), "description", [])), "html", null, true);
            echo "
          ";
            // line 40
            if (($context["display"] ?? null)) {
                // line 41
                echo "            ";
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["element"] ?? null), "display", [])), "html", null, true);
                echo "
          ";
            }
            // line 43
            echo "        </div>
      </div>
    </div>
  ";
        }
        // line 47
        echo "</div>
";
    }

    public function getTemplateName()
    {
        return "themes/contrib/claro/templates/content-edit/file-managed-file.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  98 => 47,  92 => 43,  86 => 41,  84 => 40,  80 => 39,  75 => 36,  73 => 35,  67 => 32,  61 => 30,  59 => 27,  58 => 26,  57 => 25,  56 => 24,  55 => 21,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "themes/contrib/claro/templates/content-edit/file-managed-file.html.twig", "/Users/joelb/Sites/wxt83001/html/themes/contrib/claro/templates/content-edit/file-managed-file.html.twig");
    }
}
