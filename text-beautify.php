<?php

/*
Plugin Name: Text Beautify
Plugin URI: http://rommelsantor.com/clog/2012/02/09/text-beautify-wordpress-plugin/
Description: Intelligently cleans up the case of blog post title/contents and/or comments to display in sentence case or title case, cleans up sloppy punctuation, makes quotes and commas curly, and allows other admin-customizable text enhancements. This is primarily targeted at the discerning blogger and designer type who is concerned with the aesthetics of the typewritten word.
Version: 0.6
Author: Rommel Santor
Author URI: http://rommelsantor.com
License: GPL2 - http://www.gnu.org/licenses/gpl-2.0.html
*/
/*
Text Beautify - WordPress Plugin
Copyright (c) 2012  Rommel Santor (http://rommelsantor.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class TextBeautifyPlugin {
  /**
   *
   */
  function __get_default_opts($i_key = null) {
    $opts = array(
      'enable_blog' => true,
      'enable_title' => true,
      'enable_comment' => true,
      'enable_autocase' => false,

      'title_lc' => "a\nan\nin\nthe\nif\nin\nof\nand\nat\nby\nfor\non\nor\nto\nvia\nvs\nv\nis",

      'enable_repchars' => true,
      'repchars' => '!?*,',

      'enable_quot' => true,
      'enable_apos' => true,
      'enable_comma' => true,

      'enable_custom_uc' => true,
      'custom_uc' => "Dr.\nLos Angeles\nPHP\nHTML\nAPI\nURL\nJavaScript\njQuery\niPhone\niPad\n" .
        "Sunday\nMonday\nTuesday\nWednesday\nThursday\nFriday\nSaturday\n" .
        "January\nFebruary\nMarch\nApril\nMay\nJune\nJuly\nAugust\nSeptember\nOctober\nNovember\nDecember\n" .
        "Jan\nFeb\nMar\nApr\nMay\nJun\nJul\nAug\nSep\nOct\nNov\nDec\n",

      'enable_custom_repl' => true,
      'custom_repl_from' => array('Mon.'),
      'custom_repl_to' => array('Monday'),
    );

    return $i_key ? @$opts[$i_key] : $opts;
  }

  /**
   *
   */
  function TextBeautifyPlugin() {
    $this->was_installed = false;

    if (!$this->opts = get_option('textbeautify-opts'))
      $this->opts = $this->__get_default_opts();
    else
      $this->was_installed = true;

    register_activation_hook(__FILE__, array($this, '__install'));
    register_deactivation_hook(__FILE__, array($this, '__uninstall'));

    add_action('admin_menu', array($this, '__admin_menu'));

    // disable when browsing posts in /wp-admin/
    if (strpos(@$_SERVER['REQUEST_URI'], '/wp-admin/') !== false)
      return;

    if ($this->opts['enable_blog']) {
      $cb_body = array($this, 'process_body');
      foreach (array('the_content', 'the_content_rss') as $tag)
        add_filter($tag, $cb_body);
    }

    if ($this->opts['enable_title']) {
      $cb_title = array($this, 'process_title');
      foreach (array('the_title', 'the_title_rss') as $tag)
        add_filter($tag, $cb_title);
    }

    if ($this->opts['enable_comment']) {
      foreach (array('comment_text', 'comment_text_rss') as $tag)
        add_filter($tag, $cb_body);
    }
  }

  /**
   *
   */
  function __apply_custom($i_str) {
    if ($this->opts['enable_custom_uc'] && $this->opts['custom_uc']) {
      $terms = array_map('trim', explode("\n", $this->opts['custom_uc']));
      foreach ($terms as $term) {
        if ($term = trim($term))
          $i_str = preg_replace('#(^|[^\w])' . preg_quote($term, '#') . '([^\w]|$)#i', '$1' . $term . '$2', $i_str);
      }
    }

    if ($this->opts['enable_custom_repl'] && $this->opts['custom_repl_from']) {
      $froms = $this->opts['custom_repl_from'];
      $tos = $this->opts['custom_repl_to'];

      foreach ($froms as $j => $from) {
        if ($from = trim($from)) {
          $to = $tos[$j];
          $i_str = preg_replace('#(^|[^\w])' . preg_quote($from, '#') . '([^\w]|$)#i', '$1' . $to . '$2', $i_str);
        }
      }
    }

    return $i_str;
  }

  /**
   *
   */
  function process_title($i_str) {
    $i_str = $this->process_body($i_str);

    if ($this->opts['enable_autocase']) {
      $i_str = preg_replace('#(&ldquo;</span>)([a-z])#e', '"$1" . ucfirst("$2")', $i_str);

      if (!preg_match_all('/(^\s*|\s+|[\.!?]"?\s+|&ldquo;|&#8220;)([a-z][a-z]*)/i', $i_str, $m))
        return $i_str;

      if ($this->opts['title_lc'])
        $lower_terms = array_map('trim', explode("\n", strtolower($this->opts['title_lc'])));

      foreach ($m[0] as $i => $from) {
        $word = $m[2][$i];

        if (!empty($lower_terms) && in_array($lower = strtolower($word), $lower_terms))
          $to = str_replace($word, $lower, $from);
        else
          $to = str_replace($word, ucfirst($word), $from);

        $i_str = str_replace($from, $to, $i_str);
      }
    }

    return $this->__apply_custom($i_str);
  }

  /**
   *
   */
  function process_body($i_str) {
    $urls = array();
    if (preg_match_all('#http://[^\s]+#', $i_str, $m))
      $urls = $m[0];

    $tags = array();
    if (preg_match_all('#\[[^]]+\]#', $i_str, $m)) {
      foreach ($m[0] as $orig) {
        $tmp = '<!--X' . preg_replace('#\W#', '', crc32(rand())) . 'X-->';
        $i_str = str_replace($orig, $tmp, $i_str);
        $tags[$tmp] = $orig;
      }
    }

    $pieces = array();

    // we must account for meta data so we don't process chars contained within
    if (preg_match_all('#<(?!!--)[^<>]+>#s', $i_str, $m)) {
      $splitter = '<!--++X' . preg_replace('#\W#', '', crc32(rand())) . 'X++-->';
      $placeholder = '<!--X' . preg_replace('#\W#', '', crc32(rand())) . 'X-->';

      $i_str = preg_replace('#<(?!!--)[^<>]+>#s', $splitter . $placeholder, $i_str);
      $pieces = explode($splitter, $i_str);
    }
    else
      $pieces = array($i_str);

    $comma = '<!--X' . preg_replace('#\W#', '', crc32(rand())) . 'X-->';

    foreach ($pieces as $i => $str) {
      if ($this->opts['enable_autocase']) {
        $str = strtolower($str);
        $str = preg_replace('/(\s+)i((\'(ve|m|d))?[,\s]+)/', '${1}I${2}', $str);
        $str = preg_replace('/(<[^<>]+>\s*)([a-z])/e', '"$1" . ucfirst("$2")', $str);
        $str = preg_replace('/(^\s*|&#8230;"?\s+|[\x85\.!?]"?\s+)([a-z])/e', '"$1" . ucfirst("$2")', $str);
        $str = preg_replace('/(^\s*|\s+)([a-z])(\.\s+)/e', '"$1" . ucfirst("$2") . "$3"', $str);
      }
      
      $str = $this->__apply_custom($str);

      // this should be done before the curly work in case one of those is included here
      if ($this->opts['enable_repchars']) {
        $len = strlen($this->opts['repchars']);
        for ($j = 0; $j < $len; ++$j) {
          $ch = $this->opts['repchars'][$j];
          $str = preg_replace('#(' . preg_quote($ch, '#') . '){2,}#', '$1', $str);
        }
      }

      $style = ' style="font-family:georgia' . $comma . 'serif;"';

      if ($this->opts['enable_quot']) {
        if (preg_match_all('#"([^"]+)"#', $str, $qm)) {
          foreach ($qm[0] as $j => $s)
            $str = str_replace($s,
              '<span' . $style . '>&ldquo;</span>' .
              $qm[1][$j] .
              '<span' . $style . '>&rdquo;</span>',
              $str);
        }

        $str = str_replace('&#8220;', '<span' . $style . '>&ldquo;</span>', $str);
        $str = str_replace('&#8221;', '<span' . $style . '>&rdquo;</span>', $str);
      }

      if ($this->opts['enable_apos']) {
        $str = str_replace('&#8216;', '<span' . $style . '>&lsquo;</span>', $str);
        $str = str_replace('&#8217;', '<span' . $style . '>&rsquo;</span>', $str);

        if (preg_match_all("/(\d+)(?:&#x2032;|&#8242;|[\x2032'])(\W)/", $str, $qm)) {
          foreach ($qm[0] as $j => $s)
            $str = str_replace($s,
              $qm[1][$j] .
              '<span' . $style . '>&rsquo;</span>' .
              $qm[2][$j],
              $str);
        }

        if (preg_match_all("/([a-z])'([a-z])/i", $str, $qm)) {
          foreach ($qm[0] as $j => $s)
            $str = str_replace($s,
              $qm[1][$j] .
              '<span' . $style . '>&rsquo;</span>' .
              $qm[2][$j],
              $str);
        }

        if (preg_match_all("/'([^']+)'/", $str, $qm)) {
          foreach ($qm[0] as $j => $s)
            $str = str_replace($s,
              '<span' . $style . '>&lsquo;</span>' .
              $qm[1][$j] .
              '<span' . $style . '>&rsquo;</span>',
              $str);
        }

        $str = str_replace("'", '<span' . $style . '>&rsquo;</span>', $str);
      }

      if ($this->opts['enable_comma'])
        $str = str_replace(',', '<span style="font-family:georgia,serif;">&sbquo;</span>', $str);
     
      $str = str_replace($comma, ',', $str);

      $pieces[$i] = $str;
    }

    if (@$placeholder) {
      foreach ($pieces as $i => $piece) {
        while (strpos($piece, $placeholder) !== false)
          $piece = preg_replace('#' . $placeholder . '#', array_shift($m[0]), $piece, 1);
        $pieces[$i] = $piece;
      }
    }

    $str = implode('', $pieces);

    foreach ($urls as $url)
      $str = preg_replace('#' . preg_quote($url, '#') . '#i', $url, $str);

    foreach ($tags as $tmp => $orig)
      $str = preg_replace('#' . preg_quote($tmp, '#') . '#i', $orig, $str);

    $str = preg_replace('#!rawblock(\d+)!#', '!RAWBLOCK$1!', $str);

    return $str;
  }

  /**
   *
   */
  function __install() {
    if ($this->was_installed) {
      $defopts = $this->__get_default_opts();

      foreach ($defopts as $key => $val)
        if (!array_key_exists($key, $this->opts))
          $this->opts[$key] = $val;

      $defterms = array_map('trim', explode("\n", $defopts['custom_uc']));
      $terms = array_map('trim', explode("\n", $this->opts['custom_uc']));

      foreach ($defterms as $term)
        if (!in_array($term, $terms))
          $terms[] = $term;

      $this->opts['custom_uc'] = implode("\n", $terms);
      update_option('textbeautify-opts', $this->opts);
    }
  }

  /**
   *
   */
  function __uninstall() {
  }

  /**
   *
   */
  function __admin_menu() {
    if (!current_user_can('manage_options'))
      return;
  
    add_menu_page('Text Beautify', 'Text Beautify', 'manage_options', 'text_beautify', array($this, '__admin_menu_display'));
  }

  /**
   *
   */
  function __admin_menu_process() {
    foreach ($this->opts as $k => $_)
      $this->opts[$k] = $_POST[$k];

    if (@$_POST['repl_del']) {
      foreach ($_POST['repl_del'] as $i => $_) {
        unset($this->opts['custom_repl_from'][$i]);
        unset($this->opts['custom_repl_to'][$i]);
      }
    }

    foreach ($this->opts['custom_repl_from'] as $i => $v) {
      if (!strlen($v) && !strlen($this->opts['custom_repl_to'][$i])) {
        unset($this->opts['custom_repl_from'][$i]);
        unset($this->opts['custom_repl_to'][$i]);
      }
    }

    $_POST = array();

    update_option('textbeautify-opts', $this->opts);
  }

  /**
   *
   */
  function __admin_menu_display() {
    if (@$_POST['submit_text_beautify'])
      $this->__admin_menu_process();
    
    $vars = array_merge($this->opts, @$_POST);
    ?>

    <style>
    #sentence-case em,#sentence-case h2 { font-family: georgia,"times new roman",serif; font-style: italic; }
    .pb { padding-bottom: 10px; }
    .pl { padding-left: 20px; }
    .mono { font-family: "courier new",monospace; }
    .serif { font-family: georgia,"times new roman",serif; }
    tr.repl-row td { line-height: 10px; }
    </style>

    <div id="sentence-case">
      <h1>Text Beautify Settings</h1>
      <p>
        Welcome to the Text Beautify plugin for WordPress!
      </p>
      <p>
        This plugin will clean up the text of your blog posts and/or comments
        by fixing sentence case, stripping excess punctuation, using pretty/curly
        apostrophes and quotation marks, and allow you to define your own custom
        uppercase and replacements using the form below.
      </p>

      <form method="post" action="">
        <h2>Enable Plugin</h2>

        <table border="0">
          <tr>
            <td nowrap>
              <label for="enable_blog">Enable for blog contents:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_blog" name="enable_blog" value="1" <?php echo $vars['enable_blog'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Allow blog post bodies to be processed by Text Beautify</em>
            </td>
          </tr>

          <tr>
            <td nowrap>
              <label for="enable_title">Enable for blog titles:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_title" name="enable_title" value="1" <?php echo $vars['enable_title'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Allow blog post titles to be processed by Text Beautify</em>
            </td>
          </tr>

          <tr>
            <td nowrap>
              <label for="enable_comment">Enable for comments:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_comment" name="enable_comment" value="1" <?php echo $vars['enable_comment'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Allow comments to be processed by Text Beautify</em>
            </td>
          </tr>

          <tr>
            <td nowrap>
              <label for="enable_autocase">Enable case manipulation:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_autocase" name="enable_autocase" value="1" <?php echo $vars['enable_autocase'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Allow Text Beautify to manipulate character case</em>
            </td>
          </tr>
        </table>

        <br/>
        <hr/>

        <h2>Lowercase Blog Title Terms</h2>

        <table border="0">
          <tr>
            <td colspan="2" class="pl">
              <em>Text Beautify will make each word of a blog post uppercase except for these terms:</em>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="pb pl">
              <textarea name="title_lc" style="width:400px" rows="4"><?php echo htmlentities($vars['title_lc']); ?></textarea>
              <br/><small>Enter one term per line and each will be made lowercase in blog titles</small>
            </td>
          </tr>
        </table>

        <br/>
        <hr/>

        <h2>Character Repetition</h2>

        <table border="0">
          <tr>
            <td nowrap>
              <label for="enable_repchars">Cleanse These Repetitive Chars:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_repchars" name="enable_repchars" value="1" <?php echo $vars['enable_repchars'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Disallow any of the specified characters from being over-used</em>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="pb pl">
              <input type="input" class="mono" name="repchars" size="40" value="<?php echo htmlentities($vars['repchars']); ?>"/>
              &nbsp; &nbsp;
              <em>for example,</em> include <tt>!</tt> to replace <tt>!!!!!</tt> <em>with</em> <tt>!</tt>
            </td>
          </tr>
        </table>

        <br/>
        <hr/>

        <h2>Curly Punctuation</h2>

        <table border="0">
          <tr>
            <td nowrap>
              <label for="enable_quot">Enable Curly Double Quotation Marks:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_quot" name="enable_quot" value="1" <?php echo $vars['enable_quot'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Replace</em> <tt>"these ugly quotes"</tt>
              <em>with</em>
              <tt><span class="serif">&ldquo;</span>these pretty quotes<span class="serif">&rdquo;</span></tt>
            </td>
          </tr>
          <tr>
            <td nowrap>
              <label for="enable_apos">Enable Curly Single Apostrophe Marks:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_apos" name="enable_apos" value="1" <?php echo $vars['enable_apos'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Replace</em>
              <tt>this string's ugly apostrophe</tt>
              <em>with</em>
              <tt>this string<span class="serif">&rsquo;</span>s pretty apostrophe</tt>
            </td>
          </tr>
          <tr>
            <td nowrap>
              <label for="enable_comma">Enable Curly Comma:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_comma" name="enable_comma" value="1" <?php echo $vars['enable_comma'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Replace</em>
              <tt>this plain, ugly comma</tt>
              <em>with</em>
              <tt>this pretty<span class="serif">&sbquo;</span> curly comma</tt>
            </td>
          </tr>
        </table>

        <br/>
        <hr/>

        <h2>Customizations</h2>

        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td nowrap>
              <label for="enable_custom_uc">Enable Custom Case Preservation:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_custom_uc" name="enable_custom_uc" value="1" <?php echo $vars['enable_custom_uc'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Preserve the case of all the terms as specified here:</em>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="pb pl">
              <textarea name="custom_uc" style="width:400px" rows="4"><?php echo htmlentities($vars['custom_uc']); ?></textarea>
              <br/><small>Enter one term per line and the case of each will be preserved as typed</small>
            </td>
          </tr>

          <tr>
            <td nowrap>
              <label for="enable_custom_repl">Enable Custom Replacements:</label>
            </td>
            <td>
              <input type="checkbox" id="enable_custom_repl" name="enable_custom_repl" value="1" <?php echo $vars['enable_custom_repl'] ? 'checked' : ''; ?>/>
            </td>
            <td class="pl">
              <em>Enforce these custom string replacements:</em>
            </td>
          </tr>
          <?php
          $froms = $vars['custom_repl_from'];
          $tos = $vars['custom_repl_to'];
          do {
            $from = array_shift($froms);
            $to = array_shift($tos);
            ?>
            <tr class="repl-row <?php if (!$froms) echo ' last'; ?>">
              <td class="pl">
                <label>
                  <span>From:</span>
                  <input type="text" name="custom_repl_from[]" value="<?php echo htmlentities($from); ?>" size="25"/>
                </label>
              </td>
              <td>
                <h2>&rarr;</h2>
              </td>
              <td>
                <label>
                  <span>To:</span>
                  <input type="text" name="custom_repl_to[]" value="<?php echo htmlentities($to); ?>" size="25"/>
                </label>

                &nbsp; &nbsp;
                <label>
                  <input type="checkbox" name="repl_del[]" value="1"/>
                  <small><em>delete</em></small>
                </label>
              </td>
            </tr>
          <?php
          } while ($froms); ?>
          <tr>
            <td colspan="3" class="pl">
              <a href="#" id="add-repl-row">
                Add another custom replacement
              </a>
            </td>
          </tr>
        </table>

        <br/>
        <hr/>

        <input type="submit" name="submit_text_beautify" value="Save Settings"/>
      </form>
    </div>
    <script type="text/javascript">
    /*<!--[CDATA[*/
    jQuery(document).ready(function(){
      var $lastrepl = jQuery("tr.last.repl-row");
      jQuery("#add-repl-row").click(function(){
        var $newrepl = $lastrepl.clone();
        $lastrepl.removeClass('last');
        $newrepl.insertAfter($lastrepl);
        jQuery("input", $newrepl).val('');
        $lastrepl = $newrepl;
        return false;
      });
    });
    /*]]->*/
    </script>
  <?php
  }
}

new TextBeautifyPlugin();

