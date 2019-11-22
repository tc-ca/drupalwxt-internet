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

/* themes/custom/wxt_bootstrap/templates/page/page--gcweb.html.twig */
class __TwigTemplate_c0e5fb78b3a45c7c34af0cd974e2fe28fb7095bd101f8c7816919c15eb457e14 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
            'navbar' => [$this, 'block_navbar'],
            'main' => [$this, 'block_main'],
            'highlighted' => [$this, 'block_highlighted'],
            'header' => [$this, 'block_header'],
            'breadcrumb' => [$this, 'block_breadcrumb'],
            'action_links' => [$this, 'block_action_links'],
            'help' => [$this, 'block_help'],
            'content' => [$this, 'block_content'],
            'sidebar_first' => [$this, 'block_sidebar_first'],
            'sidebar_second' => [$this, 'block_sidebar_second'],
            'footer' => [$this, 'block_footer'],
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["set" => 59, "if" => 62, "block" => 63];
        $filters = ["escape" => 200, "t" => 201, "clean_class" => 69];
        $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'block'],
                ['escape', 't', 'clean_class'],
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
        // line 59
        $context["container"] = (($this->getAttribute($this->getAttribute(($context["theme"] ?? null), "settings", []), "fluid_container", [])) ? ("container-fluid") : ("container"));
        // line 60
        echo "
";
        // line 62
        if (($this->getAttribute(($context["page"] ?? null), "navigation", []) || $this->getAttribute(($context["page"] ?? null), "navigation_collapsible", []))) {
            // line 63
            echo "  ";
            $this->displayBlock('navbar', $context, $blocks);
        }
        // line 94
        echo "
";
        // line 96
        $this->displayBlock('main', $context, $blocks);
        // line 197
        echo "
";
        // line 199
        if ((($context["gcweb_cdn_goc"] ?? null) &&  !($context["gcweb_election"] ?? null))) {
            // line 200
            echo "  <aside class=\"gc-nttvs ";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["container"] ?? null)), "html", null, true);
            echo "\">
    <h2>";
            // line 201
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Government of Canada activities and initiatives"));
            echo "</h2>
    <div id=\"gcwb_prts\" class=\"wb-eqht row\" data-ajax-replace=\"//cdn.canada.ca/gcweb-cdn-live/features/features-";
            // line 202
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["language"] ?? null)), "html", null, true);
            echo ".html\">
      <p class=\"mrgn-lft-md\">
        <a href=\"http://www.canada.ca/activities.html\">";
            // line 204
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Access Government of Canada activities and initiatives"));
            echo "</a>
      </p>
    </div>
  </aside>
";
        }
        // line 209
        echo "
";
        // line 210
        if ($this->getAttribute(($context["page"] ?? null), "footer", [])) {
            // line 211
            echo "  ";
            if (($context["gcweb_cdn_footer_enable"] ?? null)) {
                // line 212
                echo "    <footer id=\"wb-info\" data-ajax-replace=\"";
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["gcweb_cdn_footer_url"] ?? null)), "html", null, true);
                echo "\"></footer>
  ";
            } else {
                // line 214
                echo "    ";
                $this->displayBlock('footer', $context, $blocks);
                // line 236
                echo "  ";
            }
        }
    }

    // line 63
    public function block_navbar($context, array $blocks = [])
    {
        // line 64
        echo "
    ";
        // line 66
        $context["navbar_classes"] = [0 => "navbar", 1 => (($this->getAttribute($this->getAttribute(        // line 68
($context["theme"] ?? null), "settings", []), "navbar_inverse", [])) ? ("navbar-inverse") : ("navbar-default")), 2 => (($this->getAttribute($this->getAttribute(        // line 69
($context["theme"] ?? null), "settings", []), "navbar_position", [])) ? (("navbar-" . \Drupal\Component\Utility\Html::getClass($this->sandbox->ensureToStringAllowed($this->getAttribute($this->getAttribute(($context["theme"] ?? null), "settings", []), "navbar_position", []))))) : (""))];
        // line 72
        echo "    <header";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["navbar_attributes"] ?? null), "addClass", [0 => ($context["navbar_classes"] ?? null)], "method")), "html", null, true);
        echo " id=\"navbar\">
      <div id=\"wb-bnr\" class=\"";
        // line 73
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["container"] ?? null)), "html", null, true);
        echo "\">
        <section id=\"wb-lng\" class=\"text-right\">
          <h2 class=\"wb-inv\">";
        // line 75
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Language selection"));
        echo "</h2>
          ";
        // line 76
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "language_toggle", [])), "html", null, true);
        echo "
        </section>
        <div class=\"row\">
          ";
        // line 79
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "banner", [])), "html", null, true);
        echo "
          ";
        // line 80
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "search", [])), "html", null, true);
        echo "
        </div>
      </div>
      <nav class=\"gcweb-menu\" data-trgt=\"mb-pnl\" typeof=\"SiteNavigationElement\">
        <div class=\"";
        // line 84
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["container"] ?? null)), "html", null, true);
        echo "\">
          <h2 class=\"wb-inv\">";
        // line 85
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Menu"));
        echo "</h2>
          <button type=\"button\" aria-haspopup=\"true\" aria-expanded=\"false\"><span class=\"wb-inv\">Main </span>Menu <span class=\"expicon glyphicon glyphicon-chevron-down\"></span></button>
          ";
        // line 87
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "navigation", [])), "html", null, true);
        echo "
        </div>
      </nav>
      ";
        // line 90
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "breadcrumb", [])), "html", null, true);
        echo "
    </header>
  ";
    }

    // line 96
    public function block_main($context, array $blocks = [])
    {
        // line 97
        echo "
  <div class=\"";
        // line 98
        ((($context["is_front"] ?? null)) ? (print ("container-fluid")) : (print ($this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, ($context["container"] ?? null), "html", null, true))));
        echo "\">
    <div class=\"row\">

      ";
        // line 102
        echo "      ";
        if ($this->getAttribute(($context["page"] ?? null), "highlighted", [])) {
            // line 103
            echo "        ";
            $this->displayBlock('highlighted', $context, $blocks);
            // line 106
            echo "      ";
        }
        // line 107
        echo "
      ";
        // line 109
        echo "      ";
        // line 110
        $context["content_classes"] = [0 => ((($this->getAttribute(        // line 111
($context["page"] ?? null), "sidebar_first", []) && $this->getAttribute(($context["page"] ?? null), "sidebar_second", []))) ? ("col-md-6 col-md-push-3") : ("")), 1 => ((($this->getAttribute(        // line 112
($context["page"] ?? null), "sidebar_first", []) && twig_test_empty($this->getAttribute(($context["page"] ?? null), "sidebar_second", [])))) ? ("col-md-9 col-md-push-3") : ("")), 2 => ((($this->getAttribute(        // line 113
($context["page"] ?? null), "sidebar_second", []) && twig_test_empty($this->getAttribute(($context["page"] ?? null), "sidebar_first", [])))) ? ("col-md-9") : ("")), 3 => (((twig_test_empty($this->getAttribute(        // line 114
($context["page"] ?? null), "sidebar_first", [])) && twig_test_empty($this->getAttribute(($context["page"] ?? null), "sidebar_second", [])))) ? ("col-md-12") : (""))];
        // line 117
        echo "      <main role=\"main\" property=\"mainContentOfPage\" ";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["content_attributes"] ?? null), "addClass", [0 => ($context["content_classes"] ?? null), 1 => "main-container", 2 => ($context["container"] ?? null), 3 => "js-quickedit-main-content"], "method")), "html", null, true);
        echo ">

      ";
        // line 120
        echo "      ";
        if ($this->getAttribute(($context["page"] ?? null), "header", [])) {
            // line 121
            echo "        ";
            $this->displayBlock('header', $context, $blocks);
            // line 124
            echo "      ";
        }
        // line 125
        echo "
      <section>

        ";
        // line 129
        echo "        ";
        if (($context["breadcrumb"] ?? null)) {
            // line 130
            echo "          ";
            $this->displayBlock('breadcrumb', $context, $blocks);
            // line 133
            echo "        ";
        }
        // line 134
        echo "
        ";
        // line 136
        echo "        ";
        if (($context["action_links"] ?? null)) {
            // line 137
            echo "          ";
            $this->displayBlock('action_links', $context, $blocks);
            // line 140
            echo "        ";
        }
        // line 141
        echo "
        ";
        // line 143
        echo "        ";
        if ($this->getAttribute(($context["page"] ?? null), "help", [])) {
            // line 144
            echo "          ";
            $this->displayBlock('help', $context, $blocks);
            // line 147
            echo "        ";
        }
        // line 148
        echo "
        ";
        // line 150
        echo "        ";
        $this->displayBlock('content', $context, $blocks);
        // line 155
        echo "      </section>

      </main>

      ";
        // line 160
        echo "      ";
        // line 161
        $context["sidebar_first_classes"] = [0 => ((($this->getAttribute(        // line 162
($context["page"] ?? null), "sidebar_first", []) && $this->getAttribute(($context["page"] ?? null), "sidebar_second", []))) ? ("col-md-3 col-md-pull-6") : ("")), 1 => ((($this->getAttribute(        // line 163
($context["page"] ?? null), "sidebar_first", []) && twig_test_empty($this->getAttribute(($context["page"] ?? null), "sidebar_second", [])))) ? ("col-md-3 col-md-pull-9") : ("")), 2 => ((($this->getAttribute(        // line 164
($context["page"] ?? null), "sidebar_second", []) && twig_test_empty($this->getAttribute(($context["page"] ?? null), "sidebar_first", [])))) ? ("col-md-3 col-md-pull-9") : (""))];
        // line 167
        echo "      ";
        // line 168
        echo "      ";
        if ($this->getAttribute(($context["page"] ?? null), "sidebar_first", [])) {
            // line 169
            echo "        ";
            $this->displayBlock('sidebar_first', $context, $blocks);
            // line 174
            echo "      ";
        }
        // line 175
        echo "
      ";
        // line 177
        echo "      ";
        // line 178
        $context["sidebar_second_classes"] = [0 => ((($this->getAttribute(        // line 179
($context["page"] ?? null), "sidebar_first", []) && $this->getAttribute(($context["page"] ?? null), "sidebar_second", []))) ? ("col-md-3") : ("")), 1 => ((($this->getAttribute(        // line 180
($context["page"] ?? null), "sidebar_first", []) && twig_test_empty($this->getAttribute(($context["page"] ?? null), "sidebar_second", [])))) ? ("col-md-3") : ("")), 2 => ((($this->getAttribute(        // line 181
($context["page"] ?? null), "sidebar_second", []) && twig_test_empty($this->getAttribute(($context["page"] ?? null), "sidebar_first", [])))) ? ("col-md-3") : (""))];
        // line 184
        echo "      ";
        // line 185
        echo "      ";
        if ($this->getAttribute(($context["page"] ?? null), "sidebar_second", [])) {
            // line 186
            echo "        ";
            $this->displayBlock('sidebar_second', $context, $blocks);
            // line 191
            echo "      ";
        }
        // line 192
        echo "
    </div>
  </div>

";
    }

    // line 103
    public function block_highlighted($context, array $blocks = [])
    {
        // line 104
        echo "          <div class=\"highlighted\">";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "highlighted", [])), "html", null, true);
        echo "</div>
        ";
    }

    // line 121
    public function block_header($context, array $blocks = [])
    {
        // line 122
        echo "          ";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "header", [])), "html", null, true);
        echo "
        ";
    }

    // line 130
    public function block_breadcrumb($context, array $blocks = [])
    {
        // line 131
        echo "            ";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["breadcrumb"] ?? null)), "html", null, true);
        echo "
          ";
    }

    // line 137
    public function block_action_links($context, array $blocks = [])
    {
        // line 138
        echo "            <ul class=\"action-links\">";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["action_links"] ?? null)), "html", null, true);
        echo "</ul>
          ";
    }

    // line 144
    public function block_help($context, array $blocks = [])
    {
        // line 145
        echo "            ";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "help", [])), "html", null, true);
        echo "
          ";
    }

    // line 150
    public function block_content($context, array $blocks = [])
    {
        // line 151
        echo "          <a id=\"main-content\"></a>
          ";
        // line 152
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "content", [])), "html", null, true);
        echo "
          ";
        // line 153
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "content_footer", [])), "html", null, true);
        echo "
        ";
    }

    // line 169
    public function block_sidebar_first($context, array $blocks = [])
    {
        // line 170
        echo "          <nav";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["attributes"] ?? null), "addClass", [0 => ($context["sidebar_first_classes"] ?? null)], "method")), "html", null, true);
        echo ">
            ";
        // line 171
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "sidebar_first", [])), "html", null, true);
        echo "
          </nav>
        ";
    }

    // line 186
    public function block_sidebar_second($context, array $blocks = [])
    {
        // line 187
        echo "          <nav";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($this->getAttribute(($context["attributes"] ?? null), "removeClass", [0 => ($context["sidebar_first_classes"] ?? null)], "method"), "addClass", [0 => ($context["sidebar_second_classes"] ?? null)], "method")), "html", null, true);
        echo ">
            ";
        // line 188
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "sidebar_second", [])), "html", null, true);
        echo "
          </nav>
        ";
    }

    // line 214
    public function block_footer($context, array $blocks = [])
    {
        // line 215
        echo "      <footer id=\"wb-info\">
        <div class=\"landscape\">
          <div class=\"";
        // line 217
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["container"] ?? null)), "html", null, true);
        echo "\">
            ";
        // line 218
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "footer", [])), "html", null, true);
        echo "
          </div>
        </div>
        <div class=\"brand\">
          <div class=\"";
        // line 222
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["container"] ?? null)), "html", null, true);
        echo "\">
            <div class=\"row \">
              ";
        // line 224
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "branding", [])), "html", null, true);
        echo "
              <div class=\"col-xs-6 visible-sm visible-xs tofpg\">
                <a href=\"#wb-cont\">";
        // line 226
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Top of Page"));
        echo "<span class=\"glyphicon glyphicon-chevron-up\"></span></a>
              </div>
              <div class=\"col-xs-6 col-md-2 text-right\">
                <img src='";
        // line 229
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["logo_bottom_svg"] ?? null)), "html", null, true);
        echo "' alt='";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar(t("Symbol of the Government of Canada"));
        echo "' />
              </div>
            </div>
          </div>
        </div>
      </footer>
    ";
    }

    public function getTemplateName()
    {
        return "themes/custom/wxt_bootstrap/templates/page/page--gcweb.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  469 => 229,  463 => 226,  458 => 224,  453 => 222,  446 => 218,  442 => 217,  438 => 215,  435 => 214,  428 => 188,  423 => 187,  420 => 186,  413 => 171,  408 => 170,  405 => 169,  399 => 153,  395 => 152,  392 => 151,  389 => 150,  382 => 145,  379 => 144,  372 => 138,  369 => 137,  362 => 131,  359 => 130,  352 => 122,  349 => 121,  342 => 104,  339 => 103,  331 => 192,  328 => 191,  325 => 186,  322 => 185,  320 => 184,  318 => 181,  317 => 180,  316 => 179,  315 => 178,  313 => 177,  310 => 175,  307 => 174,  304 => 169,  301 => 168,  299 => 167,  297 => 164,  296 => 163,  295 => 162,  294 => 161,  292 => 160,  286 => 155,  283 => 150,  280 => 148,  277 => 147,  274 => 144,  271 => 143,  268 => 141,  265 => 140,  262 => 137,  259 => 136,  256 => 134,  253 => 133,  250 => 130,  247 => 129,  242 => 125,  239 => 124,  236 => 121,  233 => 120,  227 => 117,  225 => 114,  224 => 113,  223 => 112,  222 => 111,  221 => 110,  219 => 109,  216 => 107,  213 => 106,  210 => 103,  207 => 102,  201 => 98,  198 => 97,  195 => 96,  188 => 90,  182 => 87,  177 => 85,  173 => 84,  166 => 80,  162 => 79,  156 => 76,  152 => 75,  147 => 73,  142 => 72,  140 => 69,  139 => 68,  138 => 66,  135 => 64,  132 => 63,  126 => 236,  123 => 214,  117 => 212,  114 => 211,  112 => 210,  109 => 209,  101 => 204,  96 => 202,  92 => 201,  87 => 200,  85 => 199,  82 => 197,  80 => 96,  77 => 94,  73 => 63,  71 => 62,  68 => 60,  66 => 59,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "themes/custom/wxt_bootstrap/templates/page/page--gcweb.html.twig", "/Users/joelb/Sites/wxt83001/html/themes/custom/wxt_bootstrap/templates/page/page--gcweb.html.twig");
    }
}
