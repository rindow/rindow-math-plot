<?php
namespace Rindow\Math\Plot\Renderer;

/////////////////////////////////////////////
//////////////                 TEXT and FONTS
/////////////////////////////////////////////
class GDTextDriver
{

    protected $fonts = [];
    protected $line_spacing;


        /*
         * Controls the line spacing of multi-line labels.
         *   $which_spc : Line spacing factor for text
         * For GD text, this is the number of pixels between lines.
         * For TTF text, it controls line spacing in proportion to the normal
         * spacing defined by the font.
         */
        function SetLineSpacing($which_spc)
        {
            $this->line_spacing = $which_spc;
            return TRUE;
        }

        /*
         * Select the default font type to use.
         *   $which_ttf : True to default to TrueType, False to default to GD (fixed) fonts.
         * This also resets all font settings to the defaults.
         */
        function SetUseTTF($which_ttf)
        {
            $this->use_ttf = $which_ttf;
            return $this->SetDefaultFonts();
        }

        /*
         * Sets the directory name to look into for TrueType fonts.
         */
        function SetTTFPath($which_path)
        {
            if (!is_dir($which_path) || !is_readable($which_path)) {
                return $this->PrintError("SetTTFPath(): $which_path is not a valid path.");
            }
            $this->ttf_path = $which_path;
            return TRUE;
        }

        /*
         * Sets the default TrueType font and updates all fonts to that.
         * The default font might be a full path, or relative to the TTFPath,
         * so let SetFont check that it exists.
         * Side effects: Enables use of TrueType fonts as the default font type,
         * and resets all font settings.
         */
        function SetDefaultTTFont($which_font)
        {
            $this->default_ttfont = $which_font;
            return $this->SetUseTTF(TRUE);
        }

        /*
         * Return the default TrueType font name. If no default has been set,
         * this tries some likely candidates for a font which can be loaded.
         * If it finds one that works, that becomes the default TT font.
         * If there is no default and it cannot find a working font, it falls
         * back to the original PHPlot default (which will not likely work either).
         */
        protected function GetDefaultTTFont()
        {
            if (!isset($this->default_ttfont)) {
                // No default font yet. Try some common sans-serif fonts.
                $fonts = array('LiberationSans-Regular.ttf',  // For Linux with a correct GD font search path
                               'Verdana.ttf', 'Arial.ttf', 'Helvetica.ttf', // For Windows, maybe others
                               'ttf-liberation/LiberationSans-Regular.ttf', // For Debian, Ubuntu, and friends
                               'benjamingothic.ttf',  // Original PHPlot default
                              );
                foreach ($fonts as $font) {
                    // First try the font name alone, to see if GD can find and load it.
                    if (@imagettfbbox(10, 0, $font, "1") !== False)
                        break;
                    // If the font wasn't found, try it with the default TTF path in front.
                    $font_with_path = $this->ttf_path . DIRECTORY_SEPARATOR . $font;
                    if (@imagettfbbox(10, 0, $font_with_path, "1") !== False) {
                        $font = $font_with_path;
                        break;
                    }
                }
                // We either have a working font, or are using the last one regardless.
                $this->default_ttfont = $font;
            }
            return $this->default_ttfont;
        }

        /*
         * Sets fonts to their defaults
         */
        protected function SetDefaultFonts()
        {
            // TTF:
            if ($this->use_ttf) {
                return $this->SetFont('generic', '', 8)
                    && $this->SetFont('title', '', 14)
                    && $this->SetFont('legend', '', 8)
                    && $this->SetFont('x_label', '', 6)
                    && $this->SetFont('y_label', '', 6)
                    && $this->SetFont('x_title', '', 10)
                    && $this->SetFont('y_title', '', 10);
            }
            // Fixed GD Fonts:
            return $this->SetFont('generic', 2)
                && $this->SetFont('title', 5)
                && $this->SetFont('legend', 2)
                && $this->SetFont('x_label', 1)
                && $this->SetFont('y_label', 1)
                && $this->SetFont('x_title', 3)
                && $this->SetFont('y_title', 3);
        }

        /*
         * Select a fixed (GD) font for an element.
         * This allows using a fixed font, even with SetUseTTF(True).
         *    $which_elem : The element whose font is to be changed.
         *       One of: title legend generic x_label y_label x_title y_title
         *    $which_font : A GD font number 1-5
         *    $which_spacing (optional) : Line spacing factor
         */
        function SetFontGD($which_elem, $which_font, $which_spacing = NULL)
        {
            if ($which_font < 1 || 5 < $which_font) {
                return $this->PrintError(__FUNCTION__ . ': Font size must be 1, 2, 3, 4 or 5');
            }
            if (!$this->CheckOption($which_elem,
                                    'generic, title, legend, x_label, y_label, x_title, y_title',
                                    __FUNCTION__)) {
                return FALSE;
            }

            // Store the font parameters: name/size, char cell height and width.
            $this->fonts[$which_elem] = array('ttf' => FALSE,
                                              'font' => $which_font,
                                              'height' => ImageFontHeight($which_font),
                                              'width' => ImageFontWidth($which_font),
                                              'line_spacing' => $which_spacing);
            return TRUE;
        }

        /*
         * Select a TrueType font for an element.
         * This allows using a TrueType font, even with SetUseTTF(False).
         *    $which_elem : The element whose font is to be changed.
         *       One of: title legend generic x_label y_label x_title y_title
         *    $which_font : A TrueType font filename or pathname.
         *    $which_size : Font point size.
         *    $which_spacing (optional) : Line spacing factor
         */
        function SetFontTTF($which_elem, $which_font, $which_size = 12, $which_spacing = NULL)
        {
            if (!$this->CheckOption($which_elem,
                                    'generic, title, legend, x_label, y_label, x_title, y_title',
                                    __FUNCTION__)) {
                return FALSE;
            }

            // Empty font name means use the default font.
            if (empty($which_font))
                $which_font = $this->GetDefaultTTFont();
            $path = $which_font;

            // First try the font name directly, if not then try with path.
            // Use GD imagettfbbox() to determine if this is a valid font.
            // The return $bbox is used below, if valid.
            if (($bbox = @imagettfbbox($which_size, 0, $path, "E")) === False) {
                $path = $this->ttf_path . DIRECTORY_SEPARATOR . $which_font;
                if (($bbox = @imagettfbbox($which_size, 0, $path, "E")) === False) {
                    return $this->PrintError(__FUNCTION__ . ": Can't find TrueType font $which_font");
                }
            }

            // Calculate the font height and inherent line spacing. TrueType fonts have this information
            // internally, but PHP/GD has no way to directly access it. So get the bounding box size of
            // an upper-case character without descenders, and the baseline-to-baseline height.
            // Note: In practice, $which_size = $height, maybe +/-1 . But which_size is in points,
            // and height is in pixels, and someday GD may be able to tell the difference.
            // The character width is saved too, but not used by the normal text drawing routines - it
            // isn't necessarily a fixed-space font. It is used in DrawLegend.
            $height = $bbox[1] - $bbox[5];
            $width = $bbox[2] - $bbox[0];
            $bbox = ImageTTFBBox($which_size, 0, $path, "E\nE");
            $spacing = $bbox[1] - $bbox[5] - 2 * $height;

            // Store the font parameters:
            $this->fonts[$which_elem] = array('ttf' => TRUE,
                                              'font' => $path,
                                              'size' => $which_size,
                                              'height' => $height,
                                              'width' => $width,
                                              'spacing' => $spacing,
                                              'line_spacing' => $which_spacing);
            return TRUE;
        }

        /*
         * Select Fixed/TrueType font for an element. Which type of font is
         * selected depends on the $use_ttf class variable (see SetUseTTF()).
         * Before PHPlot supported mixing font types, only this function and
         * SetUseTTF were available to select an overall font type, but now
         * SetFontGD() and SetFontTTF() can be used for mixing font types.
         *    $which_elem : The element whose font is to be changed.
         *       One of: title legend generic x_label y_label x_title y_title
         *    $which_font : A number 1-5 for fixed fonts, or a TrueType font.
         *    $which_size : Ignored for Fixed fonts, point size for TrueType.
         *    $which_spacing (optional) : Line spacing factor
         */
        function SetFont($which_elem, $which_font, $which_size = 12, $line_spacing = NULL)
        {
            if ($this->use_ttf)
                return $this->SetFontTTF($which_elem, $which_font, $which_size, $line_spacing);
            return $this->SetFontGD($which_elem, $which_font, $line_spacing);
        }

        /*
         * Return the inter-line spacing for a font.
         * This is an internal function, used by ProcessText* and DrawLegend.
         *   $font : A font array variable.
         * Returns: Spacing, in pixels, between text lines.
         */
        protected function GetLineSpacing($font)
        {
            // Use the per-font line spacing preference, if set, else the global value:
            if (isset($font['line_spacing']))
                $line_spacing = $font['line_spacing'];
            else
                $line_spacing = $this->line_spacing;

            // For GD fonts, that is the spacing in pixels.
            // For TTF, adjust based on the 'natural' font spacing (see SetFontTTF):
            if ($font['ttf']) {
                $line_spacing = (int)($line_spacing * $font['spacing'] / 6.0);
            }
            return $line_spacing;
        }

        /*
         * Text drawing and sizing functions:
         * ProcessText is meant for use only by DrawText and SizeText.
         *    ProcessText(True, ...)  - Draw a block of text
         *    ProcessText(False, ...) - Just return ($width, $height) of
         *       the orthogonal bounding box containing the text.
         * ProcessText is further split into separate functions for GD and TTF
         * text, due to the size of the code.
         *
         * Horizontal and vertical alignment are relative to the drawing. That is:
         * vertical text (90 deg) gets centered along Y position with
         * v_align = 'center', and adjusted to the right of X position with
         * h_align = 'right'.  Another way to look at this is to say
         * that text rotation happens first, then alignment.
         *
         * Original multiple lines code submitted by Remi Ricard.
         * Original vertical code submitted by Marlin Viss.
         *
         * Text routines rewritten by ljb to fix alignment and position problems.
         * Here is my explanation and notes. More information and pictures will be
         * placed in the PHPlot Reference Manual.
         *
         *    + Process TTF text one line at a time, not as a block. (See below)
         *    + Flipped top vs bottom vertical alignment. The usual interpretation
         *  is: bottom align means bottom of the text is at the specified Y
         *  coordinate. For some reason, PHPlot did left/right the correct way,
         *  but had top/bottom reversed. I fixed it, and left the default valign
         *  argument as bottom, but the meaning of the default value changed.
         *
         *    For GD font text, only single-line text is handled by GD, and the
         *  basepoint is the upper left corner of each text line.
         *    For TTF text, multi-line text could be handled by GD, with the text
         *  basepoint at the lower left corner of the first line of text.
         *  (Behavior of TTF drawing routines on multi-line text is not documented.)
         *  But you cannot do left/center/right alignment on each line that way,
         *  or proper line spacing.
         *    Therefore, for either text type, we have to break up the text into
         *  lines and position each line independently.
         *
         *    There are 9 alignment modes: Horizontal = left, center, or right, and
         *  Vertical = top, center, or bottom. Alignment is interpreted relative to
         *  the image, not as the text is read. This makes sense when you consider
         *  for example X axis labels. They need to be centered below the marks
         *  (center, top alignment) regardless of the text angle.
         *  'Bottom' alignment really means baseline alignment.
         *
         *    GD font text is supported (by libgd) at 0 degrees and 90 degrees only.
         *  Multi-line or single line text works with any of the 9 alignment modes.
         *
         *    TTF text can be at any angle. The 9 alignment modes work for all angles,
         *  but the results might not be what you expect for multi-line text. See
         *  the PHPlot Reference Manual for pictures and details. In short, alignment
         *  applies to the orthogonal (aligned with X and Y axes) bounding box that
         *  contains the text, and to each line in the multi-line text box. Since
         *  alignment is relative to the image, 45 degree multi-line text aligns
         *  differently from 46 degree text.
         *
         *    Note that PHPlot allows multi-line text for the 3 titles, and they
         *  are only drawn at 0 degrees (main and X titles) or 90 degrees (Y title).
         *  Data labels can also be multi-line, and they can be drawn at any angle.
         *  -ljb 2007-11-03
         *
         */

        /*
         * ProcessTextGD() - Draw or size GD fixed-font text.
         * This is intended for use only by ProcessText().
         *    $draw_it : True to draw the text, False to just return the orthogonal width and height.
         *    $font : PHPlot font array (with 'ttf' = False) - see SetFontGD()
         *    $angle : Text angle in degrees. GD only supports 0 and 90. We treat >= 45 as 90, else 0.
         *    $x, $y : Reference point for the text (ignored if !$draw_it)
         *    $color : GD color index to use for drawing the text (ignored if !$draw_it)
         *    $text : The text to draw or size. Put a newline between lines.
         *    $h_factor : Horizontal alignment factor: 0(left), .5(center), or 1(right) (ignored if !$draw_it)
         *    $v_factor : Vertical alignment factor: 0(top), .5(center), or 1(bottom) (ignored if !$draw_it)
         * Returns: True, if drawing text, or an array of ($width, $height) if not.
         */
        protected function ProcessTextGD($draw_it, $font, $angle, $x, $y, $color, $text, $h_factor, $v_factor)
        {
            // Extract font parameters:
            $font_number = $font['font'];
            $font_width = $font['width'];
            $font_height = $font['height'];
            $line_spacing = $this->GetLineSpacing($font);

            // Break up the text into lines, trim whitespace, find longest line.
            // Save the lines and length for drawing below.
            $longest = 0;
            foreach (explode("\n", $text) as $each_line) {
                $lines[] = $line = trim($each_line);
                $line_lens[] = $line_len = strlen($line);
                if ($line_len > $longest) $longest = $line_len;
            }
            $n_lines = count($lines);

            // Width, height are based on font size and longest line, line count respectively.
            // These are relative to the text angle.
            $total_width = $longest * $font_width;
            $total_height = $n_lines * $font_height + ($n_lines - 1) * $line_spacing;

            if (!$draw_it) {
                if ($angle < 45) return array($total_width, $total_height);
                return array($total_height, $total_width);
            }

            $interline_step = $font_height + $line_spacing; // Line-to-line step

            if ($angle >= 45) {
                // Vertical text (90 degrees):
                // (Remember the alignment convention with vertical text)
                // For 90 degree text, alignment factors change like this:
                $temp = $v_factor;
                $v_factor = $h_factor;
                $h_factor = 1 - $temp;

                $draw_func = 'ImageStringUp';

                // Rotation matrix "R" for 90 degrees (with Y pointing down):
                $r00 = 0;  $r01 = 1;
                $r10 = -1; $r11 = 0;

            } else {
                // Horizontal text (0 degrees):
                $draw_func = 'ImageString';

                // Rotation matrix "R" for 0 degrees:
                $r00 = 1; $r01 = 0;
                $r10 = 0; $r11 = 1;
            }

            // Adjust for vertical alignment (horizontal text) or horizontal alignment (vertical text):
            $factor = (int)($total_height * $v_factor);
            $xpos = $x - $r01 * $factor;
            $ypos = $y - $r11 * $factor;

            // Debug callback provides the bounding box:
            if ($this->GetCallback('debug_textbox')) {
                if ($angle >= 45) {
                    $bbox_width  = $total_height;
                    $bbox_height = $total_width;
                    $px = $xpos;
                    $py = $ypos - (1 - $h_factor) * $total_width;
                } else {
                    $bbox_width  = $total_width;
                    $bbox_height = $total_height;
                    $px = $xpos - $h_factor * $total_width;
                    $py = $ypos;
                }
                $this->DoCallback('debug_textbox', $px, $py, $bbox_width, $bbox_height);
            }

            for ($i = 0; $i < $n_lines; $i++) {

                // Adjust for alignment of this line within the text block:
                $factor = (int)($line_lens[$i] * $font_width * $h_factor);
                $x = $xpos - $r00 * $factor;
                $y = $ypos - $r10 * $factor;

                // Call ImageString or ImageStringUp:
                $draw_func($this->img, $font_number, $x, $y, $lines[$i], $color);

                // Step to the next line of text. This is a rotation of (x=0, y=interline_spacing)
                $xpos += $r01 * $interline_step;
                $ypos += $r11 * $interline_step;
            }
            return TRUE;
        }

        /*
         * ProcessTextTTF() - Draw or size TTF text.
         * This is intended for use only by ProcessText().
         *    $draw_it : True to draw the text, False to just return the orthogonal width and height.
         *    $font : PHPlot font array (with 'ttf' = True) - see SetFontTTF()
         *    $angle : Text angle in degrees.
         *    $x, $y : Reference point for the text (ignored if !$draw_it)
         *    $color : GD color index to use for drawing the text (ignored if !$draw_it)
         *    $text : The text to draw or size. Put a newline between lines.
         *    $h_factor : Horizontal alignment factor: 0(left), .5(center), or 1(right) (ignored if !$draw_it)
         *    $v_factor : Vertical alignment factor: 0(top), .5(center), or 1(bottom) (ignored if !$draw_it)
         * Returns: True, if drawing text, or an array of ($width, $height) if not.
         */
        protected function ProcessTextTTF($draw_it, $font, $angle, $x, $y, $color, $text, $h_factor, $v_factor)
        {
            // Extract font parameters (see SetFontTTF):
            $font_file = $font['font'];
            $font_size = $font['size'];
            $font_height = $font['height'];
            $line_spacing = $this->GetLineSpacing($font);

            // Break up the text into lines, trim whitespace.
            // Calculate the total width and height of the text box at 0 degrees.
            // Save the trimmed lines and their widths for later when drawing.
            // To get uniform spacing, don't use the actual line heights.
            // Total height = Font-specific line heights plus inter-line spacing.
            // Total width = width of widest line.
            // Last Line Descent is the offset from the bottom to the text baseline.
            // Note: For some reason, ImageTTFBBox uses (-1,-1) as the reference point.
            //   So 1+bbox[1] is the baseline to bottom distance.
            $total_width = 0;
            $lastline_descent = 0;
            foreach (explode("\n", $text) as $each_line) {
                $lines[] = $line = trim($each_line);
                $bbox = ImageTTFBBox($font_size, 0, $font_file, $line);
                $line_widths[] = $width = $bbox[2] - $bbox[0];
                if ($width > $total_width) $total_width = $width;
                $lastline_descent = 1 + $bbox[1];
            }
            $n_lines = count($lines);
            $total_height = $n_lines * $font_height + ($n_lines - 1) * $line_spacing;

            // Calculate the rotation matrix for the text's angle. Remember that GD points Y down,
            // so the sin() terms change sign.
            $theta = deg2rad($angle);
            $cos_t = cos($theta);
            $sin_t = sin($theta);
            $r00 = $cos_t;    $r01 = $sin_t;
            $r10 = -$sin_t;   $r11 = $cos_t;

            // Make a bounding box of the right size, with upper left corner at (0,0).
            // By convention, the point order is: LL, LR, UR, UL.
            // Note this is still working with the text at 0 degrees.
            // When sizing text (SizeText), use the overall size with descenders.
            //   This tells the caller how much room to leave for the text.
            // When drawing text (DrawText), use the size without descenders - that
            //   is, down to the baseline. This is for accurate positioning.
            $b[0] = 0;
            if ($draw_it) {
                $b[1] = $total_height;
            } else {
                $b[1] = $total_height + $lastline_descent;
            }
            $b[2] = $total_width;  $b[3] = $b[1];
            $b[4] = $total_width;  $b[5] = 0;
            $b[6] = 0;             $b[7] = 0;

            // Rotate the bounding box, then offset to the reference point:
            for ($i = 0; $i < 8; $i += 2) {
                $x_b = $b[$i];
                $y_b = $b[$i+1];
                $c[$i]   = $x + $r00 * $x_b + $r01 * $y_b;
                $c[$i+1] = $y + $r10 * $x_b + $r11 * $y_b;
            }

            // Get an orthogonal (aligned with X and Y axes) bounding box around it, by
            // finding the min and max X and Y:
            $bbox_ref_x = $bbox_max_x = $c[0];
            $bbox_ref_y = $bbox_max_y = $c[1];
            for ($i = 2; $i < 8; $i += 2) {
                $x_b = $c[$i];
                if ($x_b < $bbox_ref_x) $bbox_ref_x = $x_b;
                elseif ($bbox_max_x < $x_b) $bbox_max_x = $x_b;
                $y_b = $c[$i+1];
                if ($y_b < $bbox_ref_y) $bbox_ref_y = $y_b;
                elseif ($bbox_max_y < $y_b) $bbox_max_y = $y_b;
            }
            $bbox_width = $bbox_max_x - $bbox_ref_x;
            $bbox_height = $bbox_max_y - $bbox_ref_y;

            if (!$draw_it) {
                // Return the bounding box, rounded up (so it always contains the text):
                return array((int)ceil($bbox_width), (int)ceil($bbox_height));
            }

            $interline_step = $font_height + $line_spacing; // Line-to-line step

            // Calculate the offsets from the supplied reference point to the
            // upper-left corner of the text.
            // Start at the reference point at the upper left corner of the bounding
            // box (bbox_ref_x, bbox_ref_y) then adjust it for the 9 point alignment.
            // h,v_factor are 0,0 for top,left, .5,.5 for center,center, 1,1 for bottom,right.
            //    $off_x = $bbox_ref_x + $bbox_width * $h_factor - $x;
            //    $off_y = $bbox_ref_y + $bbox_height * $v_factor - $y;
            // Then use that offset to calculate back to the supplied reference point x, y
            // to get the text base point.
            //    $qx = $x - $off_x;
            //    $qy = $y - $off_y;
            // Reduces to:
            $qx = 2 * $x - $bbox_ref_x - $bbox_width * $h_factor;
            $qy = 2 * $y - $bbox_ref_y - $bbox_height * $v_factor;

            // Check for debug callback. Don't calculate bounding box unless it is wanted.
            if ($this->GetCallback('debug_textbox')) {
                // Calculate the orthogonal bounding box coordinates for debug testing.

                // qx, qy is upper left corner relative to the text.
                // Calculate px,py: upper left corner (absolute) of the bounding box.
                // There are 4 equation sets for this, depending on the quadrant:
                if ($sin_t > 0) {
                    if ($cos_t > 0) {
                        // Quadrant: 0d - 90d:
                        $px = $qx; $py = $qy - $total_width * $sin_t;
                    } else {
                        // Quadrant: 90d - 180d:
                       $px = $qx + $total_width * $cos_t; $py = $qy - $bbox_height;
                    }
                } else {
                    if ($cos_t < 0) {
                        // Quadrant: 180d - 270d:
                        $px = $qx - $bbox_width; $py = $qy + $total_height * $cos_t;
                    } else {
                        // Quadrant: 270d - 360d:
                        $px = $qx + $total_height * $sin_t; $py = $qy;
                    }
                }
                $this->DoCallback('debug_textbox', $px, $py, $bbox_width, $bbox_height);
            }

            // Since alignment is applied after rotation, which parameter is used
            // to control alignment of each line within the text box varies with
            // the angle.
            //   Angle (degrees):       Line alignment controlled by:
            //  -45 < angle <= 45          h_align
            //   45 < angle <= 135         reversed v_align
            //  135 < angle <= 225         reversed h_align
            //  225 < angle <= 315         v_align
            if ($cos_t >= $sin_t) {
                if ($cos_t >= -$sin_t) $line_align_factor = $h_factor;
                else $line_align_factor = $v_factor;
            } else {
                if ($cos_t >= -$sin_t) $line_align_factor = 1-$v_factor;
                else $line_align_factor = 1-$h_factor;
            }

            // Now we have the start point, spacing and in-line alignment factor.
            // We are finally ready to start drawing the text, line by line.
            for ($i = 0; $i < $n_lines; $i++) {

                // For drawing TTF text, the reference point is the left edge of the
                // text baseline (not the lower left corner of the bounding box).
                // The following also adjusts for horizontal (relative to
                // the text) alignment of the current line within the box.
                // What is happening is rotation of this vector by the text angle:
                //    (x = (total_width - line_width) * factor, y = font_height)

                $width_factor = ($total_width - $line_widths[$i]) * $line_align_factor;
                $rx = $qx + $r00 * $width_factor + $r01 * $font_height;
                $ry = $qy + $r10 * $width_factor + $r11 * $font_height;

                // Finally, draw the text:
                ImageTTFText($this->img, $font_size, $angle, $rx, $ry, $color, $font_file, $lines[$i]);

                // Step to position of next line.
                // This is a rotation of (x=0,y=height+line_spacing) by $angle:
                $qx += $r01 * $interline_step;
                $qy += $r11 * $interline_step;
            }
            return TRUE;
        }

        /*
         * ProcessText() - Wrapper for ProcessTextTTF() and ProcessTextGD(). See notes above.
         * This is intended for use from within PHPlot only, and only by DrawText() and SizeText().
         *    $draw_it : True to draw the text, False to just return the orthogonal width and height.
         *    $font : PHPlot font array, or NULL or empty string to use 'generic'
         *    $angle : Text angle in degrees
         *    $x, $y : Reference point for the text (ignored if !$draw_it)
         *    $color : GD color index to use for drawing the text (ignored if !$draw_it)
         *    $text : The text to draw or size. Put a newline between lines.
         *    $halign : Horizontal alignment: left, center, or right (ignored if !$draw_it)
         *    $valign : Vertical alignment: top, center, or bottom (ignored if !$draw_it)
         *      Note: Alignment is relative to the image, not the text.
         * Returns: True, if drawing text, or an array of ($width, $height) if not.
         */
        protected function ProcessText($draw_it, $font, $angle, $x, $y, $color, $text, $halign, $valign)
        {
            // Empty text case:
            if ($text === '') {
                if ($draw_it) return TRUE;
                return array(0, 0);
            }

            // Calculate width and height offset factors using the alignment args:
            if ($valign == 'top') $v_factor = 0;
            elseif ($valign == 'center') $v_factor = 0.5;
            else $v_factor = 1.0; // 'bottom'
            if ($halign == 'left') $h_factor = 0;
            elseif ($halign == 'center') $h_factor = 0.5;
            else $h_factor = 1.0; // 'right'

            // Apply a default font. This is mostly for external (callback) users.
            if (empty($font)) $font = $this->fonts['generic'];

            if ($font['ttf']) {
                return $this->ProcessTextTTF($draw_it, $font, $angle, $x, $y, $color, $text,
                                             $h_factor, $v_factor);
            }
            return $this->ProcessTextGD($draw_it, $font, $angle, $x, $y, $color, $text, $h_factor, $v_factor);
        }

        /*
         * Draws a block of text. See comments above before ProcessText().
         *    $which_font : PHPlot font array, or NULL or empty string to use 'generic'
         *    $which_angle : Text angle in degrees
         *    $which_xpos, $which_ypos: Reference point for the text
         *    $which_color : GD color index to use for drawing the text
         *    $which_text :  The text to draw, with newlines (\n) between lines.
         *    $which_halign : Horizontal (relative to the image) alignment: left, center, or right.
         *    $which_valign : Vertical (relative to the image) alignment: top, center, or bottom.
         * Note: This function should be considered 'protected', and is not documented for public use.
         */
        public function DrawText($which_font, $which_angle, $which_xpos, $which_ypos, $which_color, $which_text,
                          $which_halign = 'left', $which_valign = 'bottom')
        {
            return $this->ProcessText(TRUE,
                               $which_font, $which_angle, $which_xpos, $which_ypos,
                               $which_color, $which_text, $which_halign, $which_valign);
        }

        /*
         * Returns the size of block of text. This is the orthogonal width and height of a bounding
         * box aligned with the X and Y axes of the text. Only for angle=0 is this the actual
         * width and height of the text block, but for any angle it is the amount of space needed
         * to contain the text.
         *    $which_font : PHPlot font array, or NULL or empty string to use 'generic'
         *    $which_angle : Text angle in degrees
         *    $which_text :  The text to draw, with newlines (\n) between lines.
         * Returns a two element array with: $width, $height.
         * This is just a wrapper for ProcessText() - see above.
         * Note: This function should be considered 'protected', and is not documented for public use.
         */
        public function SizeText($which_font, $which_angle, $which_text)
        {
            // Color, position, and alignment are not used when calculating the size.
            return $this->ProcessText(FALSE,
                               $which_font, $which_angle, 0, 0, 1, $which_text, '', '');
        }

}
